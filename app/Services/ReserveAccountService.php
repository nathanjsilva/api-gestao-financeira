<?php

namespace App\Services;

use App\Events\DadosFinanceirosAlterados;
use App\Models\ReserveAccount;
use App\Models\ReserveAccountEntry;
use App\Repositories\ReserveAccountEntryRepository;
use App\Repositories\ReserveAccountRepository;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

class ReserveAccountService
{
    public function __construct(
        protected ReserveAccountRepository $reserveAccountRepository,
        protected ReserveAccountEntryRepository $reserveAccountEntryRepository,
    ) {}

    public function listar(int $usuarioId, bool $incluirArquivadas = false): Collection
    {
        return $this->reserveAccountRepository->listarPorUsuarioId($usuarioId, $incluirArquivadas);
    }

    /**
     * Lista as contas do usuario com o saldo vigente na competencia
     * informada (herdando de meses anteriores quando necessario) e a
     * diferenca em relacao ao mes anterior.
     */
    public function listarComSaldo(int $usuarioId, string $competencia): Collection
    {
        $contas = $this->listar($usuarioId);
        $competenciaAnterior = Carbon::createFromFormat('Y-m', $competencia)->subMonth()->format('Y-m');

        foreach ($contas as $conta) {
            $vigente = $this->reserveAccountEntryRepository->buscarSaldoVigente($conta->id, $competencia);
            $vigenteAnterior = $this->reserveAccountEntryRepository->buscarSaldoVigente($conta->id, $competenciaAnterior);

            $saldoAtual = $vigente !== null ? (float) $vigente->balance : null;
            $saldoAnterior = $vigenteAnterior !== null ? (float) $vigenteAnterior->balance : null;

            $conta->setAttribute('saldo_info', [
                'current_balance' => $saldoAtual,
                'previous_balance' => $saldoAnterior,
                'delta' => ($saldoAtual !== null && $saldoAnterior !== null)
                    ? round($saldoAtual - $saldoAnterior, 2)
                    : null,
                'is_inherited' => $vigente !== null && $vigente->competency !== $competencia,
                'note' => $vigente?->note,
            ]);
        }

        return $contas;
    }

    public function buscarPorId(int $id, int $usuarioId): ?ReserveAccount
    {
        return $this->reserveAccountRepository->buscarPorIdEUsuarioId($id, $usuarioId);
    }

    public function criar(int $usuarioId, array $dados): ReserveAccount
    {
        $conta = $this->reserveAccountRepository->criar($usuarioId, $dados);

        event(new DadosFinanceirosAlterados($usuarioId));

        return $conta;
    }

    public function atualizar(int $id, int $usuarioId, array $dados): ReserveAccount
    {
        $conta = $this->buscarPorIdOuFalhar($id, $usuarioId);

        $this->reserveAccountRepository->atualizar($conta, $dados);

        event(new DadosFinanceirosAlterados($usuarioId));

        return $conta->refresh();
    }

    public function listarLancamentos(int $contaId, int $usuarioId): Collection
    {
        $conta = $this->buscarPorIdOuFalhar($contaId, $usuarioId);

        return $this->reserveAccountEntryRepository->listarPorContaId($conta->id);
    }

    public function definirSaldoDoMes(int $contaId, int $usuarioId, string $competencia, array $dados): ReserveAccountEntry
    {
        $conta = $this->buscarPorIdOuFalhar($contaId, $usuarioId);

        $entry = $this->reserveAccountEntryRepository->upsert($conta->id, $competencia, $dados);

        event(new DadosFinanceirosAlterados($usuarioId));

        return $entry;
    }

    public function removerSaldoDoMes(int $contaId, int $usuarioId, string $competencia): void
    {
        $conta = $this->buscarPorIdOuFalhar($contaId, $usuarioId);
        $entry = $this->reserveAccountEntryRepository->buscarPorContaIdECompetencia($conta->id, $competencia);

        if ($entry === null) {
            throw new DomainException('Nao ha saldo declarado para esta competencia.');
        }

        $this->reserveAccountEntryRepository->excluir($entry);

        event(new DadosFinanceirosAlterados($usuarioId));
    }

    protected function buscarPorIdOuFalhar(int $id, int $usuarioId): ReserveAccount
    {
        $conta = $this->reserveAccountRepository->buscarPorIdEUsuarioId($id, $usuarioId);

        if ($conta !== null) {
            return $conta;
        }

        throw new DomainException('Conta de reserva nao encontrada para este usuario.');
    }
}
