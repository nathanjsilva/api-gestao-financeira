<?php

namespace Tests\Unit;

use App\Models\Card;
use App\Models\CardCategory;
use App\Models\CardInstallment;
use App\Models\CardPurchase;
use App\Repositories\CardCategoryRepository;
use App\Repositories\CardInstallmentRepository;
use App\Repositories\CardPurchaseRepository;
use App\Repositories\CardRepository;
use App\Services\CardPurchaseService;
use PHPUnit\Framework\TestCase;

class CardPurchaseServiceTest extends TestCase
{
    private function service(): CardPurchaseService
    {
        return new CardPurchaseService(
            new CardPurchaseRepository(new CardPurchase()),
            new CardInstallmentRepository(new CardInstallment()),
            new CardRepository(new Card()),
            new CardCategoryRepository(new CardCategory()),
        );
    }

    public function test_compra_a_vista_gera_uma_unica_parcela_com_valor_total(): void
    {
        $parcelas = $this->service()->calcularParcelas(150.00, 1, 1, '2026-01');

        $this->assertCount(1, $parcelas);
        $this->assertSame(1, $parcelas[0]['installment_number']);
        $this->assertSame('2026-01', $parcelas[0]['competency']);
        $this->assertSame(150.00, $parcelas[0]['amount']);
    }

    public function test_parcelamento_desde_a_primeira_parcela_gera_todas_as_parcelas(): void
    {
        $parcelas = $this->service()->calcularParcelas(1000.00, 4, 1, '2026-01');

        $this->assertCount(4, $parcelas);
        $this->assertSame(['2026-01', '2026-02', '2026-03', '2026-04'], array_column($parcelas, 'competency'));
        $this->assertEqualsWithDelta(1000.00, array_sum(array_column($parcelas, 'amount')), 0.001);
    }

    public function test_parcelamento_a_partir_de_parcela_intermediaria_gera_apenas_as_restantes(): void
    {
        // Compra de 12x, ja na 4a parcela: devem ser geradas as parcelas 4..12 (9 parcelas)
        $parcelas = $this->service()->calcularParcelas(1200.00, 12, 4, '2026-01');

        $this->assertCount(9, $parcelas);
        $this->assertSame(4, $parcelas[0]['installment_number']);
        $this->assertSame(12, $parcelas[array_key_last($parcelas)]['installment_number']);
        // Cada parcela de uma compra de 1200/12 vale exatamente 100.00 (sem resto)
        $this->assertSame(100.00, $parcelas[0]['amount']);
    }

    public function test_arredondamento_distribui_centavos_para_as_ultimas_parcelas(): void
    {
        $parcelas = $this->service()->calcularParcelas(1000.00, 3, 1, '2026-01');

        $valores = array_column($parcelas, 'amount');

        $this->assertSame([333.33, 333.33, 333.34], $valores);
        $this->assertEqualsWithDelta(1000.00, array_sum($valores), 0.001);
    }

    public function test_parcelamento_atravessa_virada_de_ano(): void
    {
        $parcelas = $this->service()->calcularParcelas(300.00, 3, 1, '2026-11');

        $this->assertSame(['2026-11', '2026-12', '2027-01'], array_column($parcelas, 'competency'));
    }

    public function test_soma_das_parcelas_bate_exatamente_com_valor_total_mesmo_iniciando_no_meio(): void
    {
        // Regra: o valor de cada parcela deve ser o mesmo que teria na divisao original de N,
        // independentemente de a partir de qual K o registro comeca.
        $todasAsParcelas = $this->service()->calcularParcelas(100.00, 3, 1, '2026-01');
        $parcelasAPartirDaSegunda = $this->service()->calcularParcelas(100.00, 3, 2, '2026-01');

        $this->assertSame($todasAsParcelas[1]['amount'], $parcelasAPartirDaSegunda[0]['amount']);
        $this->assertSame($todasAsParcelas[2]['amount'], $parcelasAPartirDaSegunda[1]['amount']);
    }
}
