<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReserveAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_criar_conta_e_declarar_saldo_do_mes(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $conta = $this->postJson('/api/reserve-accounts', ['name' => 'Nathan'])
            ->assertCreated()
            ->json('data');

        $this->putJson("/api/reserve-accounts/{$conta['id']}/entries/2026-01", [
            'balance' => 5000,
            'note' => 'Saldo inicial',
        ])->assertSuccessful();

        $response = $this->getJson('/api/reserve-accounts?competency=2026-01');

        $response->assertOk();
        $contaListada = collect($response->json('data'))->firstWhere('id', $conta['id']);

        $this->assertEquals(5000, $contaListada['current_balance']);
        $this->assertFalse($contaListada['is_inherited']);
    }

    public function test_saldo_e_herdado_automaticamente_quando_mes_nao_e_declarado(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $conta = $this->postJson('/api/reserve-accounts', ['name' => 'Esposa'])
            ->assertCreated()
            ->json('data');

        $this->putJson("/api/reserve-accounts/{$conta['id']}/entries/2026-01", ['balance' => 3000])
            ->assertSuccessful();

        $response = $this->getJson('/api/reserve-accounts?competency=2026-03');

        $response->assertOk();
        $contaListada = collect($response->json('data'))->firstWhere('id', $conta['id']);

        $this->assertEquals(3000, $contaListada['current_balance']);
        $this->assertTrue($contaListada['is_inherited']);
    }

    public function test_reserva_atual_soma_todas_as_contas_ativas_mais_saldo_do_mes(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $categoriaEntrada = Category::create(['user_id' => $usuario->id, 'name' => 'Salário', 'type' => 'income']);
        $categoriaSaida = Category::create(['user_id' => $usuario->id, 'name' => 'Contas', 'type' => 'expense']);

        $nathan = $this->postJson('/api/reserve-accounts', ['name' => 'Nathan'])->json('data');
        $esposa = $this->postJson('/api/reserve-accounts', ['name' => 'Esposa'])->json('data');

        $this->putJson("/api/reserve-accounts/{$nathan['id']}/entries/2026-05", ['balance' => 5000])->assertSuccessful();
        $this->putJson("/api/reserve-accounts/{$esposa['id']}/entries/2026-05", ['balance' => 3000])->assertSuccessful();

        Transaction::create([
            'user_id' => $usuario->id, 'category_id' => $categoriaEntrada->id, 'description' => 'Salário',
            'amount' => 9000, 'type' => 'income', 'status' => 'paid', 'competency' => '2026-05', 'is_recurring' => false,
        ]);
        Transaction::create([
            'user_id' => $usuario->id, 'category_id' => $categoriaSaida->id, 'description' => 'Contas do mês',
            'amount' => 8500, 'type' => 'expense', 'status' => 'paid', 'competency' => '2026-05', 'is_recurring' => false,
        ]);

        $resumo = $this->getJson('/api/dashboard/monthly-summary?competency=2026-05')->assertOk()->json('data');

        $this->assertEquals(500, $resumo['remaining_amount']);
        $this->assertEquals(8500, $resumo['current_reserve']);
        $this->assertEquals(8500, $resumo['total_saved']);
    }

    public function test_conta_arquivada_deixa_de_somar_mas_mantem_historico(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $conta = $this->postJson('/api/reserve-accounts', ['name' => 'Viagem'])->json('data');
        $this->putJson("/api/reserve-accounts/{$conta['id']}/entries/2026-01", ['balance' => 1000])->assertSuccessful();

        $this->putJson("/api/reserve-accounts/{$conta['id']}", ['active' => false])->assertOk();

        $resumo = $this->getJson('/api/dashboard/monthly-summary?competency=2026-02')->assertOk()->json('data');
        $this->assertEquals(0, $resumo['current_reserve']);

        $historico = $this->getJson("/api/reserve-accounts/{$conta['id']}/entries")->assertOk()->json('data');
        $this->assertCount(1, $historico);
        $this->assertEquals(1000, $historico[0]['balance']);
    }

    public function test_excluir_saldo_do_mes_volta_a_herdar_do_mes_anterior(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $conta = $this->postJson('/api/reserve-accounts', ['name' => 'Nathan'])->json('data');
        $this->putJson("/api/reserve-accounts/{$conta['id']}/entries/2026-01", ['balance' => 5000])->assertSuccessful();
        $this->putJson("/api/reserve-accounts/{$conta['id']}/entries/2026-02", ['balance' => 2000])->assertSuccessful();

        $this->deleteJson("/api/reserve-accounts/{$conta['id']}/entries/2026-02")->assertNoContent();

        $response = $this->getJson('/api/reserve-accounts?competency=2026-02');
        $contaListada = collect($response->json('data'))->firstWhere('id', $conta['id']);

        $this->assertEquals(5000, $contaListada['current_balance']);
        $this->assertTrue($contaListada['is_inherited']);
    }
}
