# Contexto: dashboard financeiro geral

## Responsabilidade e dependências

O dashboard geral agrega transações, saldos vigentes das contas de reserva ativas e o investimento mensal para apresentar resumo, comparações, séries temporais, indicadores e insights. Ele não lê parcelas de cartão. O controller obtém o user_id do token autenticado; services e repositories filtram os dados desse usuário.

Arquivos centrais: DashboardController, DashboardService, FinancialAnalyticsService, CategoryAnalyticsService, MonthlyComparisonService, TransactionRepository, MonthlyReserveRepository, ReserveAccountEntryRepository e os Requests/Resources em app/Http/Requests/Dashboard e app/Http/Resources/Dashboard*.

## Endpoints

| Endpoint | Parâmetros | Comportamento |
|---|---|---|
| GET /api/dashboard/analytics | competency obrigatório; months opcional, padrão 6, inteiro de 2 a 12 | Painel completo, cacheado por cinco minutos. |
| GET /api/dashboard/monthly-summary | competency obrigatório | Resumo da competência e totais por categoria. |
| GET /api/dashboard/category-comparison | current_competency e previous_competency obrigatórios | Comparação de totais por categoria entre as duas competências. |
| GET /api/dashboard/monthly-evolution | start_competency e end_competency obrigatórios | Série mensal inclusiva. Se o início for posterior ao fim, responde 422. |
| GET /api/dashboard/month-comparison | first_competency e second_competency obrigatórios | Comparação dos resumos de duas competências. |

As competências usam YYYY-MM e são verificadas por CompetencyRule. As rotas do dashboard exigem autenticação.

## Resumo mensal e fórmulas

Cada resumo mensal possui competency, total_income, total_expense, remaining_amount, previous_reserve, current_reserve, investment e total_saved.

- Entradas e despesas somam todas as transações da competência, tanto paid quanto pending.
- remaining_amount = total_income - total_expense.
- previous_reserve é a soma dos snapshots vigentes das contas de reserva atualmente ativas. Para cada conta, o snapshot vigente é o mais recente com competência menor ou igual à consultada.
- current_reserve = previous_reserve + remaining_amount.
- investment é o campo investimento da reserva mensal daquela competência, ou zero quando não há registro.
- total_saved = current_reserve + investment.

previous_reserve é o nome da base de saldos das contas de reserva na competência; não é um campo persistido em MonthlyReserve nem necessariamente o saldo de encerramento da competência anterior. A fórmula pode contar duas vezes o dinheiro se o snapshot já incluir a sobra mensal.

Contas com active=false são excluídas do agregado por estado atual para todas as competências calculadas, inclusive as históricas. Isso difere da mensagem atual de confirmação na interface, que diz que o saldo deixa de entrar nos totais dos próximos meses. O histórico dos snapshots continua consultável nos endpoints de contas de reserva.

A listagem de MonthlyReserve reutiliza o mesmo resumo calculado, exposto pelo Resource como total_income, total_expense, remaining_amount, current_reserve e total_saved, junto com investimento e observations. A edição direta do campo investimento é aceita pela API; quando itens de investimento são alterados, MonthlyReserveService volta a calcular esse campo pela soma dos itens.

## Painel analítico

A resposta de /analytics tem estes blocos:

| Bloco | Conteúdo |
|---|---|
| filters | competency selecionada, previous_competency e months. |
| overview | O resumo mensal completo da competência selecionada. |
| comparison | Competências anterior/atual, diferenças e percentuais de entradas e gastos, diferença de reserva e saldo, e tendência de gastos (worse, better ou stable). |
| kpis | average_monthly_expense, average_monthly_income, average_remaining, best_month, worst_month, income_commitment_rate, savings_rate e projected_closing. |
| categories | ranking, maior e menor gasto e top_growth. Cada item inclui total, total anterior, diferença, participação percentual e tendência. |
| evolution | Resumo mensal de cada competência da janela, inclusive meses sem transações, que aparecem com valores zero. |
| reserve_evolution | Competência, previous_reserve, current_reserve, investment e total_saved por mês. |
| cash_flow | Competência, income, expense e remaining por mês; inclui transações pagas e pendentes. |
| heatmap | Itens com label da categoria, percentual das despesas e tone (critical, warning, attention ou safe). |
| insights | Mensagens geradas pelo serviço para comparação de despesas, evolução da reserva, categoria principal e taxa de economia. |

A janela inclui a competência selecionada e os meses anteriores. As médias usam todas as competências da janela; melhor e pior mês são escolhidos pelo maior e menor remaining_amount. projected_closing é o remaining_amount da competência selecionada, não uma previsão baseada em tendência futura. income_commitment_rate = despesas / entradas * 100; savings_rate = saldo restante / entradas * 100. Quando entradas são zero ou negativas, ambas as taxas retornam zero.

O ranking de categorias considera despesas da competência selecionada e compara com a competência imediatamente anterior. A série temporal retorna competências sem dados com valores zero. Os insights são calculados, não editáveis.

## Cache e invalidação

O analytics usa TTL de cinco minutos e uma chave com usuário, competência, janela e versão:

- versão: dashboard:user:{userId}:version;
- chave: dashboard:analytics:user:{userId}:{competency}:{months}:v{version}.

O listener InvalidarCacheDashboard incrementa a versão após eventos DadosFinanceirosAlterados. Escritas em transações, reservas mensais, itens de investimento e contas/snapshots de reserva emitem o evento. CRUD de categorias gerais não emite esse evento; uma resposta analítica cacheada pode manter nomes de categoria antigos até expirar.

## Integrações relevantes

- Transações dependem de categorias gerais do mesmo usuário e alimentam o resumo financeiro.
- Contas de reserva e investimento mensal alimentam reserve_evolution e current_reserve/total_saved.
- O editor de reserva consulta monthly-summary para calcular prévias da competência.
- Os painéis de cartões têm serviço, dados e cache independentes; não entram neste cálculo.