<?php

namespace App\Repositories;

use App\Models\CardInstallment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CardInstallmentRepository
{
    public function __construct(
        protected CardInstallment $model,
    ) {}

    /**
     * @param  list<array{installment_number: int, competency: string, amount: float}>  $parcelas
     */
    public function criarEmLote(
        int $compraId,
        int $userId,
        int $cardId,
        int $cardCategoryId,
        string $paymentType,
        array $parcelas,
    ): Collection {
        $agora = now();

        $linhas = array_map(fn (array $parcela): array => [
            'card_purchase_id' => $compraId,
            'user_id' => $userId,
            'card_id' => $cardId,
            'card_category_id' => $cardCategoryId,
            'payment_type' => $paymentType,
            'installment_number' => $parcela['installment_number'],
            'competency' => $parcela['competency'],
            'amount' => $parcela['amount'],
            'created_at' => $agora,
            'updated_at' => $agora,
        ], $parcelas);

        $this->model->query()->insert($linhas);

        return $this->model
            ->query()
            ->where('card_purchase_id', $compraId)
            ->orderBy('installment_number')
            ->get();
    }

    public function excluirPorCompraId(int $compraId): int
    {
        return $this->model
            ->query()
            ->where('card_purchase_id', $compraId)
            ->delete();
    }

    public function obterTotalPorCompetencia(int $userId, string $competencia): float
    {
        return (float) $this->model
            ->query()
            ->where('user_id', $userId)
            ->where('competency', $competencia)
            ->sum('amount');
    }

    public function obterTotalPorAno(int $userId, string $ano): float
    {
        return (float) $this->model
            ->query()
            ->where('user_id', $userId)
            ->where('competency', 'like', "{$ano}-%")
            ->sum('amount');
    }

    public function obterTotalAgrupadoPorCartao(int $userId, string $competencia): Collection
    {
        return $this->model
            ->query()
            ->selectRaw('card_id, SUM(amount) as total')
            ->where('user_id', $userId)
            ->where('competency', $competencia)
            ->groupBy('card_id')
            ->with('card:id,name,responsible_person')
            ->orderByDesc('total')
            ->get();
    }

    public function obterTotalAgrupadoPorPessoa(int $userId, string $competencia): Collection
    {
        return DB::table('card_installments')
            ->join('cards', 'cards.id', '=', 'card_installments.card_id')
            ->selectRaw('cards.responsible_person, SUM(card_installments.amount) as total')
            ->where('card_installments.user_id', $userId)
            ->where('card_installments.competency', $competencia)
            ->groupBy('cards.responsible_person')
            ->orderByDesc('total')
            ->get();
    }

    public function obterTotalAgrupadoPorCategoria(int $userId, string $competencia): Collection
    {
        return $this->model
            ->query()
            ->selectRaw('card_category_id, SUM(amount) as total')
            ->where('user_id', $userId)
            ->where('competency', $competencia)
            ->groupBy('card_category_id')
            ->with('category:id,name')
            ->orderByDesc('total')
            ->get();
    }

    public function obterTotalAgrupadoPorTipoPagamento(int $userId, string $competencia): Collection
    {
        return $this->model
            ->query()
            ->selectRaw('payment_type, SUM(amount) as total')
            ->where('user_id', $userId)
            ->where('competency', $competencia)
            ->groupBy('payment_type')
            ->get();
    }

    public function obterTotalComprometidoFuturo(int $userId, string $competenciaAtual): float
    {
        return (float) $this->model
            ->query()
            ->where('user_id', $userId)
            ->where('competency', '>', $competenciaAtual)
            ->sum('amount');
    }

    public function obterSaldoDevedorParcelamentosEmAberto(int $userId, string $competenciaAtual): float
    {
        return (float) $this->model
            ->query()
            ->where('user_id', $userId)
            ->where('payment_type', 'installment')
            ->where('competency', '>', $competenciaAtual)
            ->sum('amount');
    }

    /**
     * @param  list<string>  $competencias
     */
    public function obterEvolucaoMensal(int $userId, array $competencias): Collection
    {
        return $this->model
            ->query()
            ->selectRaw('competency, SUM(amount) as total')
            ->where('user_id', $userId)
            ->whereIn('competency', $competencias)
            ->groupBy('competency')
            ->orderBy('competency')
            ->get();
    }

    public function obterTopCategorias(int $userId, string $competencia, int $limite = 5): Collection
    {
        return $this->obterTotalAgrupadoPorCategoria($userId, $competencia)->take($limite)->values();
    }

    public function obterCartoesComMaisVolume(int $userId, string $competencia, int $limite = 5): Collection
    {
        return $this->obterTotalAgrupadoPorCartao($userId, $competencia)->take($limite)->values();
    }

    /**
     * @param  list<string>  $competenciasAnteriores
     */
    public function obterMediaHistoricaMensal(int $userId, array $competenciasAnteriores): float
    {
        if (empty($competenciasAnteriores)) {
            return 0.0;
        }

        $totaisPorMes = DB::table('card_installments')
            ->selectRaw('competency, SUM(amount) as total')
            ->where('user_id', $userId)
            ->whereIn('competency', $competenciasAnteriores)
            ->groupBy('competency')
            ->get();

        if ($totaisPorMes->isEmpty()) {
            return 0.0;
        }

        return (float) $totaisPorMes->avg('total');
    }
}
