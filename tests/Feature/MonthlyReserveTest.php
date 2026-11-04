<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MonthlyReserve;
use App\Models\ReserveAccount;
use App\Models\ReserveAccountEntry;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MonthlyReserveTest extends TestCase
{
    use RefreshDatabase;

    private function criarContaComSaldo(int $usuarioId, string $competencia, float $saldo): ReserveAccount
    {
        $conta = ReserveAccount::create([
            'user_id' => $usuarioId,
            'name' => 'Reserva',
            'active' => true,
        ]);

        ReserveAccountEntry::create([
            'reserve_account_id' => $conta->id,
            'competency' => $competencia,
            'balance' => $saldo,
        ]);

        return $conta;
    }

    public function test_atualizar_reserva_sem_mudar_competencia_nao_falha(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $reserva = MonthlyReserve::create([
            'user_id' => $usuario->id,
            'competency' => '2026-01',
            'investimento' => 0,
            'observations' => 'observacao original',
        ]);

        $response = $this->putJson("/api/monthly-reserves/{$reserva->id}", [
            'observations' => 'observacao atualizada',
        ]);

        $response->assertOk();
        $this->assertSame('observacao atualizada', $response->json('data.observations'));
    }

    public function test_listagem_retorna_saldo_calculado_com_transacoes_e_investimento(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $categoriaEntrada = Category::create([
            'user_id' => $usuario->id,
            'name' => 'Salário',
            'type' => 'income',
        ]);

        $categoriaSaida = Category::create([
            'user_id' => $usuario->id,
            'name' => 'Contas',
            'type' => 'expense',
        ]);

        $this->criarContaComSaldo($usuario->id, '2026-01', 10000);

        MonthlyReserve::create([
            'user_id' => $usuario->id,
            'competency' => '2026-01',
            'investimento' => 300,
            'observations' => null,
        ]);

        Transaction::create([
            'user_id' => $usuario->id,
            'category_id' => $categoriaEntrada->id,
            'description' => 'Salário',
            'amount' => 5200,
            'type' => 'income',
            'status' => 'paid',
            'competency' => '2026-01',
            'is_recurring' => false,
        ]);

        Transaction::create([
            'user_id' => $usuario->id,
            'category_id' => $categoriaSaida->id,
            'description' => 'Contas do mês',
            'amount' => 5000,
            'type' => 'expense',
            'status' => 'paid',
            'competency' => '2026-01',
            'is_recurring' => false,
        ]);

        $response = $this->getJson('/api/monthly-reserves');

        $response->assertOk();
        $reserva = collect($response->json('data'))->firstWhere('competency', '2026-01');

        $this->assertNotNull($reserva);
        $this->assertEquals(200, $reserva['remaining_amount']);
        $this->assertEquals(10200, $reserva['current_reserve']);
        $this->assertEquals(10500, $reserva['total_saved']);
    }

    public function test_lancamentos_de_investimento_mantem_total_sincronizado_ao_criar_editar_e_excluir(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $reserva = $this->postJson('/api/monthly-reserves', [
            'competency' => '2026-04',
            'investimento' => 0,
        ])->assertCreated()->json('data');

        $lancamento = $this->postJson("/api/monthly-reserves/{$reserva['id']}/entries", [
            'description' => 'Aporte em renda fixa',
            'amount' => 120,
        ])->assertCreated()->json('data');

        $this->assertSame('120.00', MonthlyReserve::findOrFail($reserva['id'])->investimento);

        $this->putJson("/api/monthly-reserves/{$reserva['id']}/entries/{$lancamento['id']}", [
            'amount' => 175,
        ])->assertOk();

        $this->assertSame('175.00', MonthlyReserve::findOrFail($reserva['id'])->investimento);

        $this->deleteJson("/api/monthly-reserves/{$reserva['id']}/entries/{$lancamento['id']}")
            ->assertNoContent();

        $this->assertSame('0.00', MonthlyReserve::findOrFail($reserva['id'])->investimento);
    }
}
