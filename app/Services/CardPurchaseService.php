<?php

namespace App\Services;

use App\Events\CartaoDadosAlterados;
use App\Models\CardPurchase;
use App\Repositories\CardCategoryRepository;
use App\Repositories\CardInstallmentRepository;
use App\Repositories\CardPurchaseRepository;
use App\Repositories\CardRepository;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CardPurchaseService
{
    public function __construct(
        protected CardPurchaseRepository $cardPurchaseRepository,
        protected CardInstallmentRepository $cardInstallmentRepository,
        protected CardRepository $cardRepository,
        protected CardCategoryRepository $cardCategoryRepository,
    ) {}

    /**
     * @param  array{competency?: string, card_id?: int, card_category_id?: int, payment_type?: string}  $filtros
     */
    public function listar(int $usuarioId, array $filtros = []): Collection
    {
        return $this->cardPurchaseRepository->listarPorUsuarioId($usuarioId, $filtros);
    }

    public function buscarPorId(int $id, int $usuarioId): ?CardPurchase
    {
        return $this->cardPurchaseRepository->buscarPorIdEUsuarioId($id, $usuarioId);
    }

    /**
     * Divide o valor total em parcelas sem erro de arredondamento: os
     * centavos que sobram da divisao inteira sao distribuidos para as
     * ultimas parcelas, garantindo que a soma de 1..N feche exatamente
     * com o valor total, independentemente de a partir de qual parcela
     * (K) o registro comeca a ser gerado.
     *
     * @return list<array{installment_number: int, competency: string, amount: float}>
     */
    public function calcularParcelas(
        float $valorTotal,
        int $quantidadeParcelas,
        int $parcelaInicial,
        string $competenciaReferencia,
    ): array {
        $totalCentavos = (int) round($valorTotal * 100);
        $baseCentavos = intdiv($totalCentavos, $quantidadeParcelas);
        $resto = $totalCentavos % $quantidadeParcelas;

        $parcelas = [];
        $competencia = Carbon::createFromFormat('Y-m', $competenciaReferencia)->startOfMonth();

        for ($numero = $parcelaInicial; $numero <= $quantidadeParcelas; $numero++) {
            $centavosDaParcela = $baseCentavos + ($numero > $quantidadeParcelas - $resto ? 1 : 0);

            $parcelas[] = [
                'installment_number' => $numero,
                'competency' => $competencia->format('Y-m'),
                'amount' => round($centavosDaParcela / 100, 2),
            ];

            $competencia->addMonth();
        }

        return $parcelas;
    }

    public function criar(int $usuarioId, array $dados): CardPurchase
    {
        $this->validarCartaoECategoria($usuarioId, $dados['card_id'], $dados['card_category_id']);

        $dadosNormalizados = $this->normalizarPagamento($dados);

        $compra = DB::transaction(function () use ($usuarioId, $dadosNormalizados): CardPurchase {
            $compra = $this->cardPurchaseRepository->criar([
                'user_id' => $usuarioId,
                'card_id' => $dadosNormalizados['card_id'],
                'card_category_id' => $dadosNormalizados['card_category_id'],
                'description' => $dadosNormalizados['description'],
                'total_amount' => $dadosNormalizados['total_amount'],
                'purchase_date' => $dadosNormalizados['purchase_date'],
                'reference_competency' => $dadosNormalizados['reference_competency'],
                'payment_type' => $dadosNormalizados['payment_type'],
                'installments_total' => $dadosNormalizados['installments_total'],
                'starting_installment_number' => $dadosNormalizados['starting_installment_number'],
            ]);

            $this->gerarParcelas($compra, $dadosNormalizados);

            return $compra;
        });

        event(new CartaoDadosAlterados($usuarioId));

        return $this->buscarPorId($compra->id, $usuarioId);
    }

    public function atualizar(int $id, int $usuarioId, array $dados): CardPurchase
    {
        $compra = $this->buscarPorIdOuFalhar($id, $usuarioId);

        $this->validarCartaoECategoria($usuarioId, $dados['card_id'], $dados['card_category_id']);

        $dadosNormalizados = $this->normalizarPagamento($dados);

        DB::transaction(function () use ($compra, $dadosNormalizados): void {
            $this->cardPurchaseRepository->atualizar($compra, [
                'card_id' => $dadosNormalizados['card_id'],
                'card_category_id' => $dadosNormalizados['card_category_id'],
                'description' => $dadosNormalizados['description'],
                'total_amount' => $dadosNormalizados['total_amount'],
                'purchase_date' => $dadosNormalizados['purchase_date'],
                'reference_competency' => $dadosNormalizados['reference_competency'],
                'payment_type' => $dadosNormalizados['payment_type'],
                'installments_total' => $dadosNormalizados['installments_total'],
                'starting_installment_number' => $dadosNormalizados['starting_installment_number'],
            ]);

            $this->cardInstallmentRepository->excluirPorCompraId($compra->id);
            $this->gerarParcelas($compra->refresh(), $dadosNormalizados);
        });

        event(new CartaoDadosAlterados($usuarioId));

        return $this->buscarPorId($id, $usuarioId);
    }

    public function excluir(int $id, int $usuarioId): void
    {
        $compra = $this->buscarPorIdOuFalhar($id, $usuarioId);

        $this->cardPurchaseRepository->excluir($compra);

        event(new CartaoDadosAlterados($usuarioId));
    }

    public function verificarQuitacao(int $id, int $usuarioId): bool
    {
        $this->buscarPorIdOuFalhar($id, $usuarioId);

        $competenciaFinal = $this->cardPurchaseRepository->obterCompetenciaFinalPorCompraId($id);

        if ($competenciaFinal === null) {
            return true;
        }

        return $competenciaFinal <= now()->format('Y-m');
    }

    protected function gerarParcelas(CardPurchase $compra, array $dados): void
    {
        $parcelas = $this->calcularParcelas(
            (float) $dados['total_amount'],
            $dados['installments_total'],
            $dados['starting_installment_number'],
            $dados['reference_competency'],
        );

        $this->cardInstallmentRepository->criarEmLote(
            $compra->id,
            $compra->user_id,
            $dados['card_id'],
            $dados['card_category_id'],
            $dados['payment_type'],
            $parcelas,
        );
    }

    protected function normalizarPagamento(array $dados): array
    {
        if ($dados['payment_type'] === 'cash') {
            $dados['installments_total'] = 1;
            $dados['starting_installment_number'] = 1;
        }

        return $dados;
    }

    protected function validarCartaoECategoria(int $usuarioId, int $cardId, int $cardCategoryId): void
    {
        $cartao = $this->cardRepository->buscarPorIdEUsuarioId($cardId, $usuarioId);

        if ($cartao === null || ! $cartao->active) {
            throw new DomainException('Cartao invalido ou inativo para este usuario.');
        }

        $categoria = $this->cardCategoryRepository->buscarPorIdEUsuarioId($cardCategoryId, $usuarioId);

        if ($categoria === null || ! $categoria->active) {
            throw new DomainException('Categoria de cartao invalida ou inativa para este usuario.');
        }
    }

    protected function buscarPorIdOuFalhar(int $id, int $usuarioId): CardPurchase
    {
        $compra = $this->buscarPorId($id, $usuarioId);

        if ($compra !== null) {
            return $compra;
        }

        throw new DomainException('Compra de cartao nao encontrada para este usuario.');
    }
}
