<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\CardCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CardPurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function criarCartaoECategoria(User $usuario, string $pessoa = 'Nathan'): array
    {
        $cartao = Card::create([
            'user_id' => $usuario->id,
            'name' => 'Cartao '.$pessoa,
            'responsible_person' => $pessoa,
            'active' => true,
        ]);

        $categoria = CardCategory::create([
            'user_id' => $usuario->id,
            'name' => 'Mercado',
            'active' => true,
        ]);

        return [$cartao, $categoria];
    }

    public function test_compra_a_vista_gera_uma_unica_parcela(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);
        [$cartao, $categoria] = $this->criarCartaoECategoria($usuario);

        $response = $this->postJson('/api/card-purchases', [
            'card_id' => $cartao->id,
            'card_category_id' => $categoria->id,
            'description' => 'Padaria',
            'total_amount' => 45.90,
            'purchase_date' => '2026-01-05',
            'reference_competency' => '2026-01',
            'payment_type' => 'cash',
        ])->assertCreated();

        $compra = $response->json('data');

        $this->assertCount(1, $compra['installments']);
        $this->assertSame(1, $compra['installments'][0]['installment_number']);
        $this->assertEquals(45.90, $compra['installments'][0]['amount']);
    }

    public function test_parcelamento_desde_a_primeira_parcela(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);
        [$cartao, $categoria] = $this->criarCartaoECategoria($usuario);

        $response = $this->postJson('/api/card-purchases', [
            'card_id' => $cartao->id,
            'card_category_id' => $categoria->id,
            'description' => 'Notebook',
            'total_amount' => 4000,
            'purchase_date' => '2026-01-10',
            'reference_competency' => '2026-01',
            'payment_type' => 'installment',
            'installments_total' => 4,
            'starting_installment_number' => 1,
        ])->assertCreated();

        $parcelas = $response->json('data.installments');

        $this->assertCount(4, $parcelas);
        $this->assertSame(['2026-01', '2026-02', '2026-03', '2026-04'], array_column($parcelas, 'competency'));
        $this->assertEqualsWithDelta(4000, array_sum(array_column($parcelas, 'amount')), 0.001);
    }

    public function test_parcelamento_a_partir_de_parcela_intermediaria(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);
        [$cartao, $categoria] = $this->criarCartaoECategoria($usuario);

        $response = $this->postJson('/api/card-purchases', [
            'card_id' => $cartao->id,
            'card_category_id' => $categoria->id,
            'description' => 'Viagem',
            'total_amount' => 1200,
            'purchase_date' => '2025-11-20',
            'reference_competency' => '2026-01',
            'payment_type' => 'installment',
            'installments_total' => 6,
            'starting_installment_number' => 3,
        ])->assertCreated();

        $parcelas = $response->json('data.installments');

        $this->assertCount(4, $parcelas);
        $this->assertSame(3, $parcelas[0]['installment_number']);
        $this->assertSame(6, $parcelas[array_key_last($parcelas)]['installment_number']);
        $this->assertSame(['2026-01', '2026-02', '2026-03', '2026-04'], array_column($parcelas, 'competency'));
    }

    public function test_parcelamento_atravessa_virada_de_ano(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);
        [$cartao, $categoria] = $this->criarCartaoECategoria($usuario);

        $response = $this->postJson('/api/card-purchases', [
            'card_id' => $cartao->id,
            'card_category_id' => $categoria->id,
            'description' => 'Presentes',
            'total_amount' => 300,
            'purchase_date' => '2026-11-15',
            'reference_competency' => '2026-11',
            'payment_type' => 'installment',
            'installments_total' => 3,
            'starting_installment_number' => 1,
        ])->assertCreated();

        $parcelas = $response->json('data.installments');

        $this->assertSame(['2026-11', '2026-12', '2027-01'], array_column($parcelas, 'competency'));
    }

    public function test_arredondamento_soma_exatamente_o_valor_total(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);
        [$cartao, $categoria] = $this->criarCartaoECategoria($usuario);

        $response = $this->postJson('/api/card-purchases', [
            'card_id' => $cartao->id,
            'card_category_id' => $categoria->id,
            'description' => 'Compra dividida em 3',
            'total_amount' => 1000,
            'purchase_date' => '2026-01-10',
            'reference_competency' => '2026-01',
            'payment_type' => 'installment',
            'installments_total' => 3,
            'starting_installment_number' => 1,
        ])->assertCreated();

        $valores = array_column($response->json('data.installments'), 'amount');

        $this->assertEquals([333.33, 333.33, 333.34], array_map('floatval', $valores));
    }

    public function test_editar_compra_regera_as_parcelas(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);
        [$cartao, $categoria] = $this->criarCartaoECategoria($usuario);

        $compra = $this->postJson('/api/card-purchases', [
            'card_id' => $cartao->id,
            'card_category_id' => $categoria->id,
            'description' => 'Celular',
            'total_amount' => 900,
            'purchase_date' => '2026-01-10',
            'reference_competency' => '2026-01',
            'payment_type' => 'installment',
            'installments_total' => 3,
            'starting_installment_number' => 1,
        ])->assertCreated()->json('data');

        $atualizado = $this->putJson("/api/card-purchases/{$compra['id']}", [
            'card_id' => $cartao->id,
            'card_category_id' => $categoria->id,
            'description' => 'Celular',
            'total_amount' => 900,
            'purchase_date' => '2026-01-10',
            'reference_competency' => '2026-01',
            'payment_type' => 'installment',
            'installments_total' => 6,
            'starting_installment_number' => 1,
        ])->assertOk()->json('data');

        $this->assertCount(6, $atualizado['installments']);
        $this->assertEqualsWithDelta(900, array_sum(array_column($atualizado['installments'], 'amount')), 0.001);
    }

    public function test_quitacao_muda_conforme_a_competencia_avanca(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);
        [$cartao, $categoria] = $this->criarCartaoECategoria($usuario);

        $compra = $this->postJson('/api/card-purchases', [
            'card_id' => $cartao->id,
            'card_category_id' => $categoria->id,
            'description' => 'Geladeira',
            'total_amount' => 300,
            'purchase_date' => '2026-01-10',
            'reference_competency' => '2026-01',
            'payment_type' => 'installment',
            'installments_total' => 3,
            'starting_installment_number' => 1,
        ])->assertCreated()->json('data');

        Carbon::setTestNow(Carbon::parse('2026-01-15'));
        $this->assertFalse(
            $this->getJson("/api/card-purchases/{$compra['id']}")->json('data.is_settled')
        );

        Carbon::setTestNow(Carbon::parse('2026-03-15'));
        $this->assertTrue(
            $this->getJson("/api/card-purchases/{$compra['id']}")->json('data.is_settled')
        );
    }

    public function test_multiplos_cartoes_pessoas_e_categorias_sao_isolados_corretamente(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        [$cartaoNathan, $categoriaMercado] = $this->criarCartaoECategoria($usuario, 'Nathan');
        $cartaoEsposa = Card::create(['user_id' => $usuario->id, 'name' => 'Cartao Esposa', 'responsible_person' => 'Esposa', 'active' => true]);
        $categoriaLazer = CardCategory::create(['user_id' => $usuario->id, 'name' => 'Lazer', 'active' => true]);

        $this->postJson('/api/card-purchases', [
            'card_id' => $cartaoNathan->id,
            'card_category_id' => $categoriaMercado->id,
            'description' => 'Mercado',
            'total_amount' => 200,
            'purchase_date' => '2026-01-05',
            'reference_competency' => '2026-01',
            'payment_type' => 'cash',
        ])->assertCreated();

        $this->postJson('/api/card-purchases', [
            'card_id' => $cartaoEsposa->id,
            'card_category_id' => $categoriaLazer->id,
            'description' => 'Cinema',
            'total_amount' => 80,
            'purchase_date' => '2026-01-06',
            'reference_competency' => '2026-01',
            'payment_type' => 'cash',
        ])->assertCreated();

        $compras = $this->getJson('/api/card-purchases?competency=2026-01')->assertOk()->json('data');
        $this->assertCount(2, $compras);

        $filtradasPorCartao = $this->getJson("/api/card-purchases?card_id={$cartaoEsposa->id}")->json('data');
        $this->assertCount(1, $filtradasPorCartao);
        $this->assertSame('Cinema', $filtradasPorCartao[0]['description']);
    }

    public function test_nao_permite_cartao_ou_categoria_de_outro_usuario(): void
    {
        $usuarioA = User::factory()->create();
        $usuarioB = User::factory()->create();

        [$cartaoDeA, $categoriaDeA] = $this->criarCartaoECategoria($usuarioA);

        Sanctum::actingAs($usuarioB);

        $this->postJson('/api/card-purchases', [
            'card_id' => $cartaoDeA->id,
            'card_category_id' => $categoriaDeA->id,
            'description' => 'Tentativa invalida',
            'total_amount' => 100,
            'purchase_date' => '2026-01-05',
            'reference_competency' => '2026-01',
            'payment_type' => 'cash',
        ])->assertStatus(422);
    }
}
