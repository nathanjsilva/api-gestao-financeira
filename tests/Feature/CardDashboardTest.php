<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\CardCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CardDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_totais_mensais_por_cartao_pessoa_e_categoria(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $cartaoNathan = Card::create(['user_id' => $usuario->id, 'name' => 'Cartao Nathan', 'responsible_person' => 'Nathan', 'active' => true]);
        $cartaoEsposa = Card::create(['user_id' => $usuario->id, 'name' => 'Cartao Esposa', 'responsible_person' => 'Esposa', 'active' => true]);
        $mercado = CardCategory::create(['user_id' => $usuario->id, 'name' => 'Mercado', 'active' => true]);
        $lazer = CardCategory::create(['user_id' => $usuario->id, 'name' => 'Lazer', 'active' => true]);

        $this->postJson('/api/card-purchases', [
            'card_id' => $cartaoNathan->id,
            'card_category_id' => $mercado->id,
            'description' => 'Mercado do mes',
            'total_amount' => 500,
            'purchase_date' => '2026-01-05',
            'reference_competency' => '2026-01',
            'payment_type' => 'cash',
        ])->assertCreated();

        $this->postJson('/api/card-purchases', [
            'card_id' => $cartaoEsposa->id,
            'card_category_id' => $lazer->id,
            'description' => 'Cinema',
            'total_amount' => 100,
            'purchase_date' => '2026-01-06',
            'reference_competency' => '2026-01',
            'payment_type' => 'cash',
        ])->assertCreated();

        $resumo = $this->getJson('/api/card-dashboard/monthly-summary?competency=2026-01')
            ->assertOk()
            ->json('data');

        $this->assertEquals(600, $resumo['total_month']);
        $this->assertCount(2, $resumo['by_card']);
        $this->assertCount(2, $resumo['by_person']);
        $this->assertCount(2, $resumo['by_category']);
    }

    public function test_comprometido_futuro_e_saldo_devedor_de_parcelamentos(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $cartao = Card::create(['user_id' => $usuario->id, 'name' => 'Cartao', 'responsible_person' => 'Nathan', 'active' => true]);
        $categoria = CardCategory::create(['user_id' => $usuario->id, 'name' => 'Compras', 'active' => true]);

        $this->postJson('/api/card-purchases', [
            'card_id' => $cartao->id,
            'card_category_id' => $categoria->id,
            'description' => 'TV parcelada',
            'total_amount' => 1200,
            'purchase_date' => '2026-01-05',
            'reference_competency' => '2026-01',
            'payment_type' => 'installment',
            'installments_total' => 4,
            'starting_installment_number' => 1,
        ])->assertCreated();

        $resumo = $this->getJson('/api/card-dashboard/monthly-summary?competency=2026-01')
            ->assertOk()
            ->json('data');

        // Parcela de jan ja e do mes atual; comprometido futuro = fev+mar+abr = 900
        $this->assertEqualsWithDelta(900, $resumo['committed_future'], 0.01);
        $this->assertEqualsWithDelta(900, $resumo['outstanding_balance'], 0.01);
    }

    public function test_cache_e_invalidado_apos_criar_uma_compra(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        [$cartao, $categoria] = [
            Card::create(['user_id' => $usuario->id, 'name' => 'Cartao', 'responsible_person' => 'Nathan', 'active' => true]),
            CardCategory::create(['user_id' => $usuario->id, 'name' => 'Mercado', 'active' => true]),
        ];

        $this->getJson('/api/card-dashboard/analytics?competency=2026-01')->assertOk();
        $versaoInicial = Cache::get("cards:user:{$usuario->id}:version", 1);

        $this->postJson('/api/card-purchases', [
            'card_id' => $cartao->id,
            'card_category_id' => $categoria->id,
            'description' => 'Compra',
            'total_amount' => 100,
            'purchase_date' => '2026-01-05',
            'reference_competency' => '2026-01',
            'payment_type' => 'cash',
        ])->assertCreated();

        $versaoApos = Cache::get("cards:user:{$usuario->id}:version", 1);

        $this->assertGreaterThan($versaoInicial, $versaoApos);

        $analytics = $this->getJson('/api/card-dashboard/analytics?competency=2026-01')
            ->assertOk()
            ->json('data');

        $this->assertEquals(100, $analytics['overview']['total_month']);
    }

    public function test_media_historica_inclui_competencias_sem_compras(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $cartao = Card::create([
            'user_id' => $usuario->id,
            'name' => 'Cartao',
            'responsible_person' => 'Nathan',
            'active' => true,
        ]);
        $categoria = CardCategory::create([
            'user_id' => $usuario->id,
            'name' => 'Compras',
            'active' => true,
        ]);

        $this->postJson('/api/card-purchases', [
            'card_id' => $cartao->id,
            'card_category_id' => $categoria->id,
            'description' => 'Compra de marco',
            'total_amount' => 100,
            'purchase_date' => '2026-03-05',
            'reference_competency' => '2026-03',
            'payment_type' => 'cash',
        ])->assertCreated();

        $this->postJson('/api/card-purchases', [
            'card_id' => $cartao->id,
            'card_category_id' => $categoria->id,
            'description' => 'Compra de junho',
            'total_amount' => 50,
            'purchase_date' => '2026-06-05',
            'reference_competency' => '2026-06',
            'payment_type' => 'cash',
        ])->assertCreated();

        $analytics = $this->getJson('/api/card-dashboard/analytics?competency=2026-06&months=4')
            ->assertOk()
            ->json('data');

        $this->assertTrue(collect($analytics['insights'])->contains(
            fn (array $insight): bool => $insight['title'] === 'Gasto acima da média'
        ));
    }

    public function test_compras_de_cartao_nao_alteram_o_resumo_financeiro_mas_aparecem_por_categoria_no_analytics(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $cartao = Card::create(['user_id' => $usuario->id, 'name' => 'Cartao', 'responsible_person' => 'Nathan', 'active' => true]);
        $categoria = CardCategory::create(['user_id' => $usuario->id, 'name' => 'Mercado', 'active' => true]);

        $antes = $this->getJson('/api/dashboard/monthly-summary?competency=2026-01')->assertOk()->json('data');

        $this->postJson('/api/card-purchases', [
            'card_id' => $cartao->id,
            'card_category_id' => $categoria->id,
            'description' => 'Compra',
            'total_amount' => 500,
            'purchase_date' => '2026-01-05',
            'reference_competency' => '2026-01',
            'payment_type' => 'cash',
        ])->assertCreated();

        $depois = $this->getJson('/api/dashboard/monthly-summary?competency=2026-01')->assertOk()->json('data');

        $this->assertEquals($antes['total_expense'], $depois['total_expense']);
        $this->assertEquals($antes['remaining_amount'], $depois['remaining_amount']);

        $analytics = $this->getJson('/api/dashboard/analytics?competency=2026-01')
            ->assertOk()
            ->json('data');

        $this->assertSame(0.0, (float) $analytics['overview']['total_expense']);
        $this->assertSame(500.0, (float) $analytics['card_expenses']['total']);
        $this->assertSame('Mercado', $analytics['card_expenses']['categories'][0]['category_name']);
        $this->assertSame(500.0, (float) $analytics['card_expenses']['categories'][0]['total']);
    }

    public function test_analytics_do_dashboard_atualiza_compras_de_cartao_e_isola_por_usuario(): void
    {
        $usuario = User::factory()->create();
        $outroUsuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $cartao = Card::create(['user_id' => $usuario->id, 'name' => 'Cartao', 'responsible_person' => 'Nathan', 'active' => true]);
        $categoria = CardCategory::create(['user_id' => $usuario->id, 'name' => 'Mercado', 'active' => true]);
        $cartaoAlheio = Card::create(['user_id' => $outroUsuario->id, 'name' => 'Outro cartao', 'responsible_person' => 'Outra pessoa', 'active' => true]);
        $categoriaAlheia = CardCategory::create(['user_id' => $outroUsuario->id, 'name' => 'Lazer', 'active' => true]);

        $this->getJson('/api/dashboard/analytics?competency=2026-01')
            ->assertOk()
            ->assertJsonPath('data.card_expenses.total', 0);

        $this->postJson('/api/card-purchases', [
            'card_id' => $cartaoAlheio->id,
            'card_category_id' => $categoriaAlheia->id,
            'description' => 'Compra de outra pessoa',
            'total_amount' => 999,
            'purchase_date' => '2026-01-05',
            'reference_competency' => '2026-01',
            'payment_type' => 'cash',
        ])->assertUnprocessable();

        $this->postJson('/api/card-purchases', [
            'card_id' => $cartao->id,
            'card_category_id' => $categoria->id,
            'description' => 'Compra do usuario',
            'total_amount' => 250,
            'purchase_date' => '2026-01-05',
            'reference_competency' => '2026-01',
            'payment_type' => 'installment',
            'installments_total' => 2,
            'starting_installment_number' => 1,
        ])->assertCreated();

        $analytics = $this->getJson('/api/dashboard/analytics?competency=2026-01')
            ->assertOk()
            ->json('data');

        $this->assertSame(125.0, (float) $analytics['card_expenses']['total']);
        $this->assertCount(1, $analytics['card_expenses']['categories']);
        $this->assertSame('Mercado', $analytics['card_expenses']['categories'][0]['category_name']);
    }
}
