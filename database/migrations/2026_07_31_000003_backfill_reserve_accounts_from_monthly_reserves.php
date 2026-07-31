<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const string NOME_CONTA_MIGRADA = 'Reserva';

    private const string NOTA_MIGRACAO = 'Migrado do campo reserva_anterior';

    /**
     * Run the migrations.
     *
     * Cria uma conta de reserva "Reserva" por usuario (se ainda nao existir)
     * e replica cada valor de reserva_anterior existente como uma entry
     * dessa conta, sem alterar a tabela monthly_reserves.
     */
    public function up(): void
    {
        $reservas = DB::table('monthly_reserves')
            ->where('reserva_anterior', '>', 0)
            ->get(['user_id', 'competency', 'reserva_anterior']);

        $contaIdPorUsuario = [];

        foreach ($reservas as $reserva) {
            $contaId = $contaIdPorUsuario[$reserva->user_id] ?? null;

            if ($contaId === null) {
                $contaExistente = DB::table('reserve_accounts')
                    ->where('user_id', $reserva->user_id)
                    ->where('name', self::NOME_CONTA_MIGRADA)
                    ->first(['id']);

                $contaId = $contaExistente->id ?? DB::table('reserve_accounts')->insertGetId([
                    'user_id' => $reserva->user_id,
                    'name' => self::NOME_CONTA_MIGRADA,
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $contaIdPorUsuario[$reserva->user_id] = $contaId;
            }

            $jaTemEntry = DB::table('reserve_account_entries')
                ->where('reserve_account_id', $contaId)
                ->where('competency', $reserva->competency)
                ->exists();

            if ($jaTemEntry) {
                continue;
            }

            DB::table('reserve_account_entries')->insert([
                'reserve_account_id' => $contaId,
                'competency' => $reserva->competency,
                'balance' => $reserva->reserva_anterior,
                'note' => self::NOTA_MIGRACAO,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $contasIds = DB::table('reserve_account_entries')
            ->where('note', self::NOTA_MIGRACAO)
            ->pluck('reserve_account_id')
            ->unique();

        DB::table('reserve_account_entries')
            ->where('note', self::NOTA_MIGRACAO)
            ->delete();

        foreach ($contasIds as $contaId) {
            $aindaTemEntries = DB::table('reserve_account_entries')
                ->where('reserve_account_id', $contaId)
                ->exists();

            if (! $aindaTemEntries) {
                DB::table('reserve_accounts')->where('id', $contaId)->delete();
            }
        }
    }
};
