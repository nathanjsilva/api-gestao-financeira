<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Transaction;
use App\Repositories\CategoryRepository;
use App\Repositories\TransactionRepository;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

class TransactionService
{
    public function __construct(
        protected TransactionRepository $transactionRepository,
        protected CategoryRepository $categoryRepository,
    ) {}

    public function listar(int $usuarioId, string $competencia): Collection
    {
        return $this->transactionRepository->listarPorUsuarioIdECompetencia($usuarioId, $competencia);
    }

    public function buscarPorId(int $id, int $usuarioId): ?Transaction
    {
        return $this->transactionRepository->buscarPorIdEUsuarioId($id, $usuarioId);
    }

    public function criar(int $usuarioId, array $dados): Transaction
    {
        $categoria = $this->validarCategoriaDaTransacao(
            $dados['category_id'],
            $usuarioId,
            $dados['type'],
        );

        return $this->transactionRepository->criar([
            'user_id' => $usuarioId,
            'category_id' => $categoria->id,
            'description' => $dados['description'],
            'amount' => $dados['amount'],
            'type' => $dados['type'],
            'status' => $dados['status'],
            'competency' => $dados['competency'],
            'is_recurring' => $dados['is_recurring'],
        ])->load('category:id,name,type');
    }

    public function atualizar(int $id, int $usuarioId, array $dados): Transaction
    {
        $transacao = $this->buscarTransacaoOuFalhar($id, $usuarioId);

        $tipo = $dados['type'] ?? $transacao->type;
        $categoriaId = $dados['category_id'] ?? $transacao->category_id;

        $categoria = $this->validarCategoriaDaTransacao($categoriaId, $usuarioId, $tipo);

        $dadosAtualizados = array_merge($dados, [
            'category_id' => $categoria->id,
            'type' => $tipo,
        ]);

        $this->transactionRepository->atualizar($transacao, $dadosAtualizados);

        return $transacao->refresh()->load('category:id,name,type');
    }

    public function excluir(int $id, int $usuarioId): void
    {
        $transacao = $this->buscarTransacaoOuFalhar($id, $usuarioId);

        $this->transactionRepository->excluir($transacao);
    }

    protected function buscarTransacaoOuFalhar(int $id, int $usuarioId): Transaction
    {
        $transacao = $this->buscarPorId($id, $usuarioId);

        if ($transacao !== null) {
            return $transacao;
        }

        throw new DomainException('Transacao nao encontrada para este usuario.');
    }

    protected function validarCategoriaDaTransacao(int $categoriaId, int $usuarioId, string $tipo): Category
    {
        $categoria = $this->categoryRepository->buscarPorIdTipoEUsuarioId($categoriaId, $usuarioId, $tipo);

        if ($categoria !== null) {
            return $categoria;
        }

        throw new DomainException('A categoria informada nao pertence ao usuario ou nao corresponde ao tipo da transacao.');
    }
}
