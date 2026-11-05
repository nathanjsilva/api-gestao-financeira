<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $jaConcluida = DB::table('legacy_investment_migration_state')->where('id', 1)->exists();

        $this->arquivarTabelaMensal();
        $this->arquivarTabelaDeLancamentos();
        $this->verificarArquivamento(! $jaConcluida);

        DB::transaction(function (): void {
            DB::table('monthly_reserves')->update(['investimento' => 0]);
            DB::table('monthly_reserve_entries')->update(['amount' => 0]);
            DB::table('legacy_investment_migration_state')->updateOrInsert(
                ['id' => 1],
                ['completed_at' => now()],
            );
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::table('legacy_monthly_reserve_archives')
                ->orderBy('source_id')
                ->chunk(500, function ($reservas): void {
                    foreach ($reservas as $reserva) {
                        DB::table('monthly_reserves')->updateOrInsert(
                            ['id' => $reserva->source_id],
                            [
                                'user_id' => $reserva->user_id,
                                'competency' => $reserva->competency,
                                'investimento' => $reserva->investimento,
                                'observations' => $reserva->observations,
                                'created_at' => $reserva->source_created_at,
                                'updated_at' => $reserva->source_updated_at,
                            ],
                        );
                    }
                });

            DB::table('legacy_monthly_reserve_entry_archives')
                ->orderBy('source_id')
                ->chunk(500, function ($lancamentos): void {
                    foreach ($lancamentos as $lancamento) {
                        DB::table('monthly_reserve_entries')->updateOrInsert(
                            ['id' => $lancamento->source_id],
                            [
                                'monthly_reserve_id' => $lancamento->monthly_reserve_id,
                                'description' => $lancamento->description,
                                'amount' => $lancamento->amount,
                                'created_at' => $lancamento->source_created_at,
                                'updated_at' => $lancamento->source_updated_at,
                            ],
                        );
                    }
                });

            DB::table('legacy_investment_migration_state')->where('id', 1)->delete();
        });
    }

    private function arquivarTabelaMensal(): void
    {
        DB::table('monthly_reserves')
            ->orderBy('id')
            ->chunk(500, function ($reservas): void {
                $arquivos = $reservas->map(fn (object $reserva): array => [
                    'source_id' => $reserva->id,
                    'user_id' => $reserva->user_id,
                    'competency' => $reserva->competency,
                    'investimento' => $reserva->investimento,
                    'observations' => $reserva->observations,
                    'source_created_at' => $reserva->created_at,
                    'source_updated_at' => $reserva->updated_at,
                    'archived_at' => now(),
                ])->all();

                DB::table('legacy_monthly_reserve_archives')->insertOrIgnore($arquivos);
            });
    }

    private function arquivarTabelaDeLancamentos(): void
    {
        DB::table('monthly_reserve_entries')
            ->orderBy('id')
            ->chunk(500, function ($lancamentos): void {
                $arquivos = $lancamentos->map(fn (object $lancamento): array => [
                    'source_id' => $lancamento->id,
                    'monthly_reserve_id' => $lancamento->monthly_reserve_id,
                    'description' => $lancamento->description,
                    'amount' => $lancamento->amount,
                    'source_created_at' => $lancamento->created_at,
                    'source_updated_at' => $lancamento->updated_at,
                    'archived_at' => now(),
                ])->all();

                DB::table('legacy_monthly_reserve_entry_archives')->insertOrIgnore($arquivos);
            });
    }

    private function verificarArquivamento(bool $verificarValoresOriginais): void
    {
        $reservasOriginais = DB::table('monthly_reserves')->count();
        $reservasArquivadas = DB::table('legacy_monthly_reserve_archives')->count();
        $lancamentosOriginais = DB::table('monthly_reserve_entries')->count();
        $lancamentosArquivados = DB::table('legacy_monthly_reserve_entry_archives')->count();

        if ($reservasOriginais !== $reservasArquivadas || $lancamentosOriginais !== $lancamentosArquivados) {
            throw new RuntimeException('A cópia dos dados legados está incompleta; os valores não foram zerados.');
        }

        if ($verificarValoresOriginais) {
            $this->verificarDadosDasReservas();
            $this->verificarDadosDosLancamentos();
        }

        $reservaSemArquivo = DB::table('monthly_reserves as origem')
            ->leftJoin('legacy_monthly_reserve_archives as arquivo', 'arquivo.source_id', '=', 'origem.id')
            ->whereNull('arquivo.source_id')
            ->exists();

        $lancamentoSemArquivo = DB::table('monthly_reserve_entries as origem')
            ->leftJoin('legacy_monthly_reserve_entry_archives as arquivo', 'arquivo.source_id', '=', 'origem.id')
            ->whereNull('arquivo.source_id')
            ->exists();

        if ($reservaSemArquivo || $lancamentoSemArquivo) {
            throw new RuntimeException('Há registros legados sem cópia recuperável; os valores não foram zerados.');
        }
    }

    private function verificarDadosDasReservas(): void
    {
        DB::table('monthly_reserves')->orderBy('id')->chunk(500, function ($reservas): void {
            $arquivos = DB::table('legacy_monthly_reserve_archives')
                ->whereIn('source_id', $reservas->pluck('id'))
                ->get()
                ->keyBy('source_id');

            foreach ($reservas as $reserva) {
                $arquivo = $arquivos->get($reserva->id);

                if ($arquivo === null
                    || (int) $arquivo->user_id !== (int) $reserva->user_id
                    || $arquivo->competency !== $reserva->competency
                    || (float) $arquivo->investimento !== (float) $reserva->investimento
                    || $arquivo->observations !== $reserva->observations
                    || ! $this->mesmoTimestamp($arquivo->source_created_at, $reserva->created_at)
                    || ! $this->mesmoTimestamp($arquivo->source_updated_at, $reserva->updated_at)) {
                    throw new RuntimeException('A cópia de uma reserva mensal diverge da origem; os valores não foram zerados.');
                }
            }
        });
    }

    private function verificarDadosDosLancamentos(): void
    {
        DB::table('monthly_reserve_entries')->orderBy('id')->chunk(500, function ($lancamentos): void {
            $arquivos = DB::table('legacy_monthly_reserve_entry_archives')
                ->whereIn('source_id', $lancamentos->pluck('id'))
                ->get()
                ->keyBy('source_id');

            foreach ($lancamentos as $lancamento) {
                $arquivo = $arquivos->get($lancamento->id);

                if ($arquivo === null
                    || (int) $arquivo->monthly_reserve_id !== (int) $lancamento->monthly_reserve_id
                    || $arquivo->description !== $lancamento->description
                    || (float) $arquivo->amount !== (float) $lancamento->amount
                    || ! $this->mesmoTimestamp($arquivo->source_created_at, $lancamento->created_at)
                    || ! $this->mesmoTimestamp($arquivo->source_updated_at, $lancamento->updated_at)) {
                    throw new RuntimeException('A cópia de um lançamento diverge da origem; os valores não foram zerados.');
                }
            }
        });
    }

    private function mesmoTimestamp(?string $arquivo, ?string $origem): bool
    {
        if ($arquivo === null || $origem === null) {
            return $arquivo === $origem;
        }

        return substr($arquivo, 0, 19) === substr($origem, 0, 19);
    }
};
