<?php

namespace Tests\Feature;

use App\Models\MonthlyReserve;
use App\Models\MonthlyReserveEntry;
use App\Models\ReserveAccount;
use App\Models\ReserveAccountEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyInvestmentMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_arquiva_uma_vez_zera_apenas_dados_legados_e_restaura_no_rollback(): void
    {
        $usuario = User::factory()->create();
        $reservaMensal = MonthlyReserve::create([
            'user_id' => $usuario->id,
            'competency' => '2025-12',
            'investimento' => 725,
            'observations' => 'registro original',
        ]);
        $lancamento = MonthlyReserveEntry::create([
            'monthly_reserve_id' => $reservaMensal->id,
            'description' => 'Aporte histórico',
            'amount' => 725,
        ]);
        $novoInvestimento = ReserveAccount::create([
            'user_id' => $usuario->id,
            'name' => 'Investimento novo',
            'type' => 'investment',
            'active' => true,
        ]);
        ReserveAccountEntry::create([
            'reserve_account_id' => $novoInvestimento->id,
            'competency' => '2025-12',
            'balance' => 725,
        ]);

        $migration = require base_path('database/migrations/2026_11_04_000003_archive_and_zero_legacy_investments.php');
        $migration->up();
        $migration->up();

        $this->assertSame('0.00', $reservaMensal->fresh()->investimento);
        $this->assertSame('0.00', $lancamento->fresh()->amount);
        $this->assertSame('725.00', $novoInvestimento->entries()->firstOrFail()->balance);
        $this->assertDatabaseHas('legacy_monthly_reserve_archives', [
            'source_id' => $reservaMensal->id,
            'user_id' => $usuario->id,
            'competency' => '2025-12',
            'investimento' => '725.00',
            'observations' => 'registro original',
        ]);
        $this->assertDatabaseHas('legacy_monthly_reserve_entry_archives', [
            'source_id' => $lancamento->id,
            'monthly_reserve_id' => $reservaMensal->id,
            'description' => 'Aporte histórico',
            'amount' => '725.00',
        ]);
        $this->assertSame(
            $reservaMensal->created_at->format('Y-m-d H:i:s'),
            DB::table('legacy_monthly_reserve_archives')->where('source_id', $reservaMensal->id)->value('source_created_at'),
        );
        $this->assertSame(1, DB::table('legacy_monthly_reserve_archives')->count());
        $this->assertSame(1, DB::table('legacy_monthly_reserve_entry_archives')->count());

        DB::table('monthly_reserves')->where('id', $reservaMensal->id)->delete();
        $migration->down();

        $this->assertSame('725.00', MonthlyReserve::findOrFail($reservaMensal->id)->investimento);
        $this->assertSame('725.00', MonthlyReserveEntry::findOrFail($lancamento->id)->amount);
        $this->assertSame('725.00', $novoInvestimento->entries()->firstOrFail()->balance);
    }
}
