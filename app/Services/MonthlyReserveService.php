<?php

namespace App\Services;

use App\Models\MonthlyReserve;
use App\Repositories\MonthlyReserveRepository;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

class MonthlyReserveService
{
    public function __construct(
        protected MonthlyReserveRepository $monthlyReserveRepository,
    ) {}

    public function listar(int $usuarioId): Collection
    {
        return $this->monthlyReserveRepository->listarPorUsuarioId($usuarioId);
    }

    public function buscarPorCompetencia(int $usuarioId, string $competencia): ?MonthlyReserve
    {
        return $this->monthlyReserveRepository->buscarPorUsuarioIdECompetencia($usuarioId, $competencia);
    }

    public function buscarPorId(int $id, int $usuarioId): ?MonthlyReserve
    {
        return $this->monthlyReserveRepository->buscarPorIdEUsuarioId($id, $usuarioId);
    }

    public function criar(int $usuarioId, array $dados): MonthlyReserve
    {
        $reservaExistente = $this->buscarPorCompetencia($usuarioId, $dados['competency']);

        if ($reservaExistente !== null) {
            throw new DomainException('Ja existe uma reserva mensal cadastrada para esta competencia.');
        }

        return $this->monthlyReserveRepository->criar([
            'user_id' => $usuarioId,
            'competency' => $dados['competency'],
            'reserva_anterior' => $dados['reserva_anterior'],
            'investimento' => $dados['investimento'],
            'observations' => $dados['observations'] ?? null,
        ]);
    }

    public function atualizar(int $id, int $usuarioId, array $dados): MonthlyReserve
    {
        $reservaMensal = $this->buscarPorIdOuFalhar($id, $usuarioId);

        if (isset($dados['competency']) && $dados['competency'] !== $reservaMensal->competency) {
            $reservaExistente = $this->buscarPorCompetencia($usuarioId, $dados['competency']);

            if ($reservaExistente !== null) {
                throw new DomainException('Ja existe uma reserva mensal cadastrada para a competencia informada.');
            }
        }

        $this->monthlyReserveRepository->atualizar($reservaMensal, $dados);

        return $reservaMensal->refresh();
    }

    public function excluir(int $id, int $usuarioId): void
    {
        $reservaMensal = $this->buscarPorIdOuFalhar($id, $usuarioId);

        $this->monthlyReserveRepository->excluir($reservaMensal);
    }

    protected function buscarPorIdOuFalhar(int $id, int $usuarioId): MonthlyReserve
    {
        $reservaMensal = $this->monthlyReserveRepository->buscarPorIdEUsuarioId($id, $usuarioId);

        if ($reservaMensal !== null) {
            return $reservaMensal;
        }

        throw new DomainException('Reserva mensal nao encontrada para este usuario.');
    }
}
