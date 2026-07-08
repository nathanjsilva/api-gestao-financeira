<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const string DESCRICAO_MIGRACAO = 'Aporte migrado do registro anterior';

    /**
     * Run the migrations.
     *
     * Cria um lancamento equivalente para cada reserva mensal ja existente,
     * preservando o valor de "investimento" ja cadastrado sem alterar a
     * tabela monthly_reserves.
     */
    public function up(): void
    {
        $reservas = DB::table('monthly_reserves')
            ->where('investimento', '>', 0)
            ->get(['id', 'investimento', 'observations']);

        foreach ($reservas as $reserva) {
            $jaTemLancamento = DB::table('monthly_reserve_entries')
                ->where('monthly_reserve_id', $reserva->id)
                ->exists();

            if ($jaTemLancamento) {
                continue;
            }

            DB::table('monthly_reserve_entries')->insert([
                'monthly_reserve_id' => $reserva->id,
                'description' => $reserva->observations ?: self::DESCRICAO_MIGRACAO,
                'amount' => $reserva->investimento,
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
        DB::table('monthly_reserve_entries')
            ->where('description', self::DESCRICAO_MIGRACAO)
            ->delete();
    }
};
