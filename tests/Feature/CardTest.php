<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\CardCategory;
use App\Models\CardPurchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CardTest extends TestCase
{
    use RefreshDatabase;

    public function test_criar_listar_atualizar_e_excluir_cartao(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $cartao = $this->postJson('/api/cards', [
            'name' => 'Nubank Roxinho',
            'responsible_person' => 'Nathan',
        ])->assertCreated()->json('data');

        $this->assertTrue($cartao['active']);

        $this->getJson('/api/cards')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Nubank Roxinho']);

        $this->putJson("/api/cards/{$cartao['id']}", ['active' => false])
            ->assertOk()
            ->assertJsonPath('data.active', false);

        $this->deleteJson("/api/cards/{$cartao['id']}")->assertNoContent();
    }

    public function test_uma_pessoa_pode_ter_mais_de_um_cartao(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $this->postJson('/api/cards', ['name' => 'Cartao 1', 'responsible_person' => 'Nathan'])->assertCreated();
        $this->postJson('/api/cards', ['name' => 'Cartao 2', 'responsible_person' => 'Nathan'])->assertCreated();

        $response = $this->getJson('/api/cards')->assertOk();

        $this->assertCount(2, $response->json('data'));
    }

    public function test_nao_pode_excluir_cartao_com_compras_vinculadas(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $cartao = Card::create(['user_id' => $usuario->id, 'name' => 'Cartao', 'responsible_person' => 'Nathan', 'active' => true]);
        $categoria = CardCategory::create(['user_id' => $usuario->id, 'name' => 'Mercado', 'active' => true]);

        CardPurchase::create([
            'user_id' => $usuario->id,
            'card_id' => $cartao->id,
            'card_category_id' => $categoria->id,
            'description' => 'Compras do mes',
            'total_amount' => 100,
            'purchase_date' => '2026-01-10',
            'reference_competency' => '2026-01',
            'payment_type' => 'cash',
            'installments_total' => 1,
            'starting_installment_number' => 1,
        ]);

        $this->deleteJson("/api/cards/{$cartao->id}")
            ->assertStatus(422);
    }

    public function test_usuario_nao_acessa_cartao_de_outro_usuario(): void
    {
        $usuarioA = User::factory()->create();
        $usuarioB = User::factory()->create();

        Sanctum::actingAs($usuarioA);
        $cartao = $this->postJson('/api/cards', ['name' => 'Cartao A', 'responsible_person' => 'Nathan'])
            ->assertCreated()->json('data');

        Sanctum::actingAs($usuarioB);
        $this->getJson("/api/cards/{$cartao['id']}")->assertStatus(404);
    }
}
