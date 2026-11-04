# Auditoria funcional financeira

## Escopo

Revisão documental do comportamento presente no código de autenticação, categorias, transações, reservas, dashboards e cartões. As regras descritas são observações da implementação; propostas de produto estão separadas das regras atuais. O AGENTS.md é a referência normativa de arquitetura, segurança e processo.

## Fluxos confirmados

### Transações e categorias

Uma transação pertence ao usuário autenticado e a uma categoria geral do mesmo usuário e tipo. A consulta exige competência YYYY-MM. Os relatórios financeiros incluem estados paid e pending. is_recurring não gera lançamentos futuros. Alterar o tipo de uma categoria não altera nem revalida transações existentes.

### Reserva e investimento

ReserveAccountEntry representa o saldo declarado de uma conta para uma competência. Competências sem declaração herdam a última declaração anterior. PUT /reserve-account-balances grava os snapshots enviados em uma transação; renomeações de contas são requests separadas.

MonthlyReserve agrupa a competência, observações e investimento. MonthlyReserveEntry guarda itens; mutações nos itens recalculam o investimento pela soma dentro de uma transação com bloqueio do registro mensal. A API ainda permite gravar investimento diretamente, de modo que o campo pode divergir dos itens até uma mutação posterior de item.

O dashboard calcula:

~~~text
saldo restante = receitas - despesas
reserva base = soma dos snapshots vigentes das contas atualmente ativas
reserva atual = reserva base + saldo restante
total guardado = reserva atual + investimento
~~~

Transações pagas e pendentes entram nas receitas e despesas. O estado active atual das contas afeta consultas históricas também. Snapshots são saldos, não movimentações; se já incluírem a sobra do mês, a soma pode contar o valor duas vezes.

### Cartões

Uma compra depende de cartão e categoria de cartão ativos do mesmo usuário. O Service gera parcelas; o dashboard agrega card_installments. Na edição, o backend recria todas as parcelas em uma transação. O helper do frontend é apenas uma prévia.

A distribuição usa centavos inteiros e entrega o resto às parcelas finais. A parcela inicial pode ser maior que 1; nesse caso, só as parcelas restantes são persistidas, preservando a divisão calculada para o total original de parcelas.

installment_schedule_ended significa que o cronograma previsto alcançou sua competência final. is_settled existe por compatibilidade e não representa confirmação de pagamento. O módulo não modela fatura, vencimento, liquidação, pagamento parcial ou integração contábil com transações gerais.

### Separação e cache

O dashboard financeiro geral agrega transações, snapshots de contas ativas e investimento mensal. O dashboard de cartões agrega parcelas. Os módulos não compartilham seus agregados nem a invalidação de cache.

Os eventos financeiros invalidam a versão do dashboard geral. Eventos de cartões invalidam uma versão separada. Categorias gerais não emitem evento de invalidação; nomes usados pelo analytics podem permanecer cacheados até o TTL de cinco minutos.

## Limitações de interpretação

1. Status de transação não separa valores pagos de pendentes nos relatórios.
2. O marcador de recorrência não cria novas transações.
3. O significado de abertura/fechamento do snapshot não está definido pelo código; a fórmula da reserva depende dessa distinção.
4. Investimento pode ser gravado diretamente pela API ou derivado dos itens.
5. Parcelas por competência não correspondem a ciclo real de fatura nem comprovam quitação.
6. Categorias de cartão não têm mapeamento para categorias financeiras gerais.
7. Categorias financeiras podem ter o tipo alterado sem reclassificação das transações já associadas.
8. Arquivar conta de reserva a exclui dos agregados históricos, pois o cálculo filtra pelo estado atual.
9. As rotas individuais de snapshot de conta restringem a competência ao padrão YYYY-MM, mas não aplicam CompetencyRule para verificar se o mês é válido. A listagem com filtro e a gravação em lote aplicam a regra completa.

## Decisões de produto ainda necessárias

### Saldo das contas de reserva

Definir se o saldo declarado é abertura ou fechamento da competência. A fórmula atual soma saldo vigente e saldo restante; não altere essa regra sem uma decisão de negócio aprovada.

### Transações pendentes

Definir se os indicadores devem representar orçamento previsto, realizado ou ambos em métricas separadas. A implementação atual agrega paid e pending.

### Compras de cartão e despesas gerais

Definir como evitar dupla contagem antes de relacionar uma compra, suas parcelas e eventual pagamento de fatura com transações financeiras. A implementação atual mantém os módulos isolados.

### Arquivamento de contas

A implementação filtra contas inativas também em competências passadas. A mensagem da interface pode sugerir efeito apenas futuro; alinhar a redação com a semântica desejada antes de mudar código.

## Referências

- [Fluxos, contratos, fórmulas e cache](fluxograma-arquitetura-aplicacao.md)
- [Contextos backend por módulo](../.ai/backend/contexts/)
- [AGENTS.md — padrões normativos](../AGENTS.md)

Esta revisão é documental. Nenhum resultado de teste, build ou execução de comandos é afirmado aqui.