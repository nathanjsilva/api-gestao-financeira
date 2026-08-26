<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\CardCategory;
use App\Models\CardPurchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CardCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_criar_listar_atualizar_e_excluir_categoria(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $categoria = $this->postJson('/api/card-categories', ['name' => 'Alimentação'])
            ->assertCreated()->json('data');

        $this->getJson('/api/card-categories')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Alimentação']);

        $this->putJson("/api/card-categories/{$categoria['id']}", ['name' => 'Alimentacao e Bebidas'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Alimentacao e Bebidas');

        $this->deleteJson("/api/card-categories/{$categoria['id']}")->assertNoContent();
    }

    public function test_nao_permite_categorias_duplicadas_para_o_mesmo_usuario(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $this->postJson('/api/card-categories', ['name' => 'Lazer'])->assertCreated();
        $this->postJson('/api/card-categories', ['name' => 'Lazer'])->assertStatus(422);
    }

    public function test_nao_pode_excluir_categoria_com_compras_vinculadas(): void
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

        $this->deleteJson("/api/card-categories/{$categoria->id}")
            ->assertStatus(422);
    }
}
