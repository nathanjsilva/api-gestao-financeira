<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ReserveAccount;
use App\Models\ReserveAccountEntry;
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

    public function test_reserva_atual_soma_somente_saldos_declarados_das_contas_ativas(): void
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
        $this->assertEquals(8000, $resumo['current_reserve']);
        $this->assertEquals(8000, $resumo['total_saved']);
    }

    public function test_dashboard_separa_reservas_investimentos_e_transacoes_pendentes(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $categoria = Category::create(['user_id' => $usuario->id, 'name' => 'Renda', 'type' => 'income']);
        $categoriaDespesa = Category::create(['user_id' => $usuario->id, 'name' => 'Contas', 'type' => 'expense']);
        $reserva = $this->postJson('/api/reserve-accounts', ['name' => 'Conta corrente'])->assertCreated()->json('data');
        $investimento = $this->postJson('/api/reserve-accounts', ['name' => 'CDB', 'type' => 'investment'])
            ->assertCreated()->json('data');
        $this->putJson("/api/reserve-accounts/{$reserva['id']}/entries/2026-04", ['balance' => 800])->assertSuccessful();
        $this->putJson("/api/reserve-accounts/{$investimento['id']}/entries/2026-04", ['balance' => 300])->assertSuccessful();
        $this->putJson("/api/reserve-accounts/{$reserva['id']}/entries/2026-05", ['balance' => 900])->assertSuccessful();
        $this->putJson("/api/reserve-accounts/{$investimento['id']}/entries/2026-05", ['balance' => 400])->assertSuccessful();

        Transaction::create([
            'user_id' => $usuario->id, 'category_id' => $categoria->id, 'description' => 'Recebido',
            'amount' => 100, 'type' => 'income', 'status' => 'paid', 'competency' => '2026-05', 'is_recurring' => false,
        ]);
        Transaction::create([
            'user_id' => $usuario->id, 'category_id' => $categoria->id, 'description' => 'A receber',
            'amount' => 50, 'type' => 'income', 'status' => 'pending', 'competency' => '2026-05', 'is_recurring' => false,
        ]);
        Transaction::create([
            'user_id' => $usuario->id, 'category_id' => $categoriaDespesa->id, 'description' => 'Conta pendente',
            'amount' => 20, 'type' => 'expense', 'status' => 'pending', 'competency' => '2026-05', 'is_recurring' => false,
        ]);

        $resumo = $this->getJson('/api/dashboard/monthly-summary?competency=2026-05')->assertOk()->json('data');

        $this->assertEquals(900, $resumo['reserve_total']);
        $this->assertEquals(400, $resumo['investment_total']);
        $this->assertEquals(1300, $resumo['total_saved']);
        $this->assertEquals(100, $resumo['realized_result']);
        $this->assertEquals(50, $resumo['pending_income']);
        $this->assertEquals(20, $resumo['pending_expense']);
        $this->assertEquals(130, $resumo['projected_result']);
        $this->assertEquals(200, $resumo['asset_variation']);
    }

    public function test_conta_arquivada_sem_data_conhecida_exige_revisao_e_preserva_o_historico(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);
        $conta = ReserveAccount::create([
            'user_id' => $usuario->id,
            'name' => 'Conta legada arquivada',
            'active' => false,
            'archive_status_needs_review' => true,
        ]);
        ReserveAccountEntry::create([
            'reserve_account_id' => $conta->id,
            'competency' => '2026-01',
            'balance' => 1000,
        ]);

        $antesDaRevisao = $this->getJson('/api/dashboard/monthly-summary?competency=2026-01')
            ->assertOk()->json('data');
        $this->assertEquals(0, $antesDaRevisao['reserve_total']);
        $this->assertTrue($antesDaRevisao['has_unresolved_archived_items']);

        $this->putJson("/api/reserve-accounts/{$conta->id}", [
            'active' => false,
            'competency' => '2026-02',
        ])->assertOk();

        $historicoConfirmado = $this->getJson('/api/dashboard/monthly-summary?competency=2026-01')
            ->assertOk()->json('data');
        $this->assertEquals(1000, $historicoConfirmado['reserve_total']);
        $this->assertFalse($historicoConfirmado['has_unresolved_archived_items']);

        $depoisDoArquivo = $this->getJson('/api/dashboard/monthly-summary?competency=2026-02')
            ->assertOk()->json('data');
        $this->assertEquals(0, $depoisDoArquivo['reserve_total']);
    }

    public function test_conta_arquivada_deixa_de_somar_mas_mantem_historico(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $conta = $this->postJson('/api/reserve-accounts', ['name' => 'Viagem'])->json('data');
        $this->putJson("/api/reserve-accounts/{$conta['id']}/entries/2026-01", ['balance' => 1000])->assertSuccessful();

        $this->putJson("/api/reserve-accounts/{$conta['id']}", ['active' => false, 'competency' => '2026-02'])->assertOk();

        $resumoAnterior = $this->getJson('/api/dashboard/monthly-summary?competency=2026-01')->assertOk()->json('data');
        $this->assertEquals(1000, $resumoAnterior['reserve_total']);

        $this->getJson('/api/reserve-accounts?competency=2026-02')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $arquivada = $this->getJson('/api/reserve-accounts?competency=2026-02&include_archived=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->json('data.0');
        $this->assertFalse($arquivada['active']);

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

    public function test_atualizar_saldos_de_varias_contas_em_lote(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $contaA = $this->postJson('/api/reserve-accounts', ['name' => 'Emergencia'])->json('data');
        $contaB = $this->postJson('/api/reserve-accounts', ['name' => 'Viagem'])->json('data');

        $this->putJson('/api/reserve-account-balances', [
            'competency' => '2026-05',
            'entries' => [
                ['account_id' => $contaA['id'], 'balance' => 1500, 'note' => 'Conta de emergencia'],
                ['account_id' => $contaB['id'], 'balance' => 800],
            ],
        ])->assertOk()->assertJsonCount(2, 'data');

        $this->assertDatabaseHas('reserve_account_entries', [
            'reserve_account_id' => $contaA['id'],
            'competency' => '2026-05',
            'balance' => '1500.00',
        ]);
        $this->assertDatabaseHas('reserve_account_entries', [
            'reserve_account_id' => $contaB['id'],
            'competency' => '2026-05',
            'balance' => '800.00',
        ]);
    }

    public function test_lote_invalido_nao_persiste_saldo_de_outra_conta(): void
    {
        $usuario = User::factory()->create();
        $outroUsuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $contaDoUsuario = $this->postJson('/api/reserve-accounts', ['name' => 'Emergencia'])->json('data');
        $contaDeOutroUsuario = $this->actingAs($outroUsuario)
            ->postJson('/api/reserve-accounts', ['name' => 'Privada'])
            ->json('data');

        Sanctum::actingAs($usuario);

        $this->putJson('/api/reserve-account-balances', [
            'competency' => '2026-05',
            'entries' => [
                ['account_id' => $contaDoUsuario['id'], 'balance' => 1500],
                ['account_id' => $contaDeOutroUsuario['id'], 'balance' => 900],
            ],
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('reserve_account_entries', [
            'reserve_account_id' => $contaDoUsuario['id'],
            'competency' => '2026-05',
        ]);
    }
}
