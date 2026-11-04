# Fluxo e arquitetura da aplicação

Este documento descreve a implementação encontrada no repositório. O AGENTS.md é a fonte normativa para padrões e regras universais; as configurações, migrations e código atuais determinam o comportamento efetivo.

## Visão geral

| Camada | Implementação | Papel |
|---|---|---|
| SPA | Vue 3, TypeScript, Vite, Vue Router, Pinia, Axios, Tailwind CSS 4 e ApexCharts | Navegação, formulários, sessão local e apresentação. |
| API | Laravel 13, Sanctum; Composer exige PHP ^8.3 e a imagem Docker atual usa PHP 8.4 com Apache | Autenticação, validação, autorização, domínio, cálculos e JSON. |
| Persistência | Eloquent e banco configurado pelo ambiente | Entidades, relações, restrições e agregações. O Compose fornece MySQL 8. |
| Cache | Cache configurável do Laravel | Dashboards analíticos com TTL de cinco minutos e versões isoladas por usuário. |
| Execução local | Aplicação web/PHP e banco no Compose; Vite separado | API em localhost:8000 e SPA em localhost:5173. |

A SPA tem entrada em frontend/index.html e frontend/src/main.ts. O Compose não tem serviço para o Vite nem publica frontend/dist. A rota web / do Laravel serve welcome, não a SPA. O cliente usa VITE_API_BASE_URL, com padrão http://localhost:8000/api. Redis está disponível no Compose, mas o uso depende do driver de cache configurado.

Não há integração bancária, ciclo de fatura, pagamento de cartão, recorrência agendada ou processamento de domínio por fila implementados.

## Fluxo ponta a ponta

~~~mermaid
flowchart LR
    Browser["Navegador"] --> Router["Vue Router"]
    Router --> Guards["Guards: presença de sessão local"]
    Guards --> Pages["Páginas e componentes Vue"]
    Pages --> Services["Services HTTP por módulo"]
    Services --> Client["httpClient Axios compartilhado"]
    Client -->|"JSON e Bearer quando há token"| Api["Laravel API /api"]

    subgraph API["Processamento da API"]
        ForceJson["ForceJsonResponse e throttle da API"]
        PublicRoutes["Rotas públicas: register e login"]
        Auth["auth:sanctum: rotas privadas"]
        Request["Form Request: valida e autoriza entrada"]
        Controller["Controller: HTTP, identidade autenticada e resposta"]
        Service["Service: regras de negócio"]
        Repository["Repository: acesso a dados"]
        Model["Model Eloquent"]
        Api --> ForceJson
        ForceJson --> PublicRoutes
        ForceJson --> Auth
        Auth --> Request
        PublicRoutes --> Request
        Request --> Controller --> Service --> Repository --> Model
    end

    Model --> DB[("Banco relacional")]
    DB --> Model
    Controller -->|"Resource ou JSON direto"| Api
    Api --> Client
    Client --> Pages
    Client -. "401: limpa sessão e redireciona ao login" .-> Guards

    Service -. "evento após mutação financeira" .-> FinanceEvent["DadosFinanceirosAlterados"]
    Service -. "evento após mutação de cartão" .-> CardEvent["CartaoDadosAlterados"]
    FinanceEvent --> FinanceListener["InvalidarCacheDashboard"]
    CardEvent --> CardListener["InvalidarCacheCartoes"]
    FinanceListener --> FinanceCache[("Cache geral, versão por usuário")]
    CardListener --> CardCache[("Cache de cartões, versão por usuário")]
~~~

O throttle específico limita cadastro e login. O middleware da API força negociação JSON. As rotas privadas ainda passam por auth:sanctum; os guards do navegador servem à navegação e não substituem essa autorização.

### Chamada autenticada

1. O guard do Vue verifica a sessão local e permite ou redireciona a navegação.
2. A página chama o service do módulo.
3. httpClient envia Accept e Content-Type JSON e acrescenta Authorization Bearer quando existe token.
4. A API força JSON e aplica o throttle. auth:sanctum resolve a identidade nas rotas privadas.
5. O Form Request valida e autoriza a entrada. O Controller usa apenas dados validados e a identidade autenticada.
6. O Service aplica regras do domínio e coordena o Repository. O Repository acessa Models Eloquent.
7. O Controller retorna Resource ou JSON direto. O service Vue interpreta response.data.data quando a resposta usa Resource.
8. Em 401, o interceptor limpa a sessão do Pinia/localStorage e redireciona para login.

### Autenticação

~~~mermaid
sequenceDiagram
    actor Usuario
    participant SPA as Vue e authStore
    participant API as Laravel
    participant Request as RegisterRequest ou LoginRequest
    participant Service as AuthService
    participant DB as Banco e tokens Sanctum

    Usuario->>SPA: Envia cadastro ou login
    SPA->>API: POST /api/auth/register ou /api/auth/login
    API->>Request: Normaliza email; valida campos
    Request->>Service: Dados validados
    Service->>DB: Localiza ou cria usuário; confere hash no login
    Service->>DB: Cria token pessoal Sanctum
    API-->>SPA: JSON com message e data do usuário/token
    SPA->>SPA: Persiste usuário e token no authStore
    SPA->>API: Chamadas privadas com Bearer token
    API->>DB: auth:sanctum resolve usuário
    Usuario->>SPA: Solicita logout
    SPA->>API: POST /api/auth/logout
    API->>DB: Revoga somente o token atual
    API-->>SPA: Confirma logout
    SPA->>SPA: Limpa a sessão local
~~~

O registro exige nome, email único e senha confirmada com mínimo de oito caracteres. RegisterRequest e LoginRequest removem espaços do email e convertem para minúsculas. O cadastro e o login têm limitadores próprios em AppServiceProvider. O logout remove o token atual; outros tokens do usuário não são revogados.

## Mapa de navegação da SPA

| Caminho | Página | Função |
|---|---|---|
| /login | pages/auth/LoginPage.vue | Login público; visitante. |
| /cadastro | pages/auth/RegisterPage.vue | Cadastro público; visitante. |
| /dashboard | pages/dashboard/DashboardPage.vue | Dashboard financeiro geral. |
| /categorias | pages/categories/CategoriesPage.vue | Categorias de entrada e saída. |
| /transacoes | pages/transactions/TransactionsPage.vue | Transações de uma competência e filtros locais. |
| /reserva-mensal | pages/monthly-reserve/MonthlyReservePage.vue | Contas, snapshots, itens de investimento e resumo mensal. |
| /cartoes/compras | pages/card-purchases/CardPurchasesPage.vue | Compras, parcelas da competência e dashboard de cartões. |
| /cartoes/gerenciar | pages/cards/CardsPage.vue | Gerenciar e reativar cartões. |
| /cartoes/categorias | pages/card-categories/CardCategoriesPage.vue | Gerenciar e reativar categorias de cartão. |
| /cartoes/analise | — | Redireciona para /cartoes/compras. |

AuthenticatedLayout contém a navegação privada; PublicLayout atende autenticação. Os services Vue centralizam chamadas por módulo e usam o httpClient comum.

## Entidades e relações

~~~mermaid
erDiagram
    USERS ||--o{ CATEGORIES : owns
    USERS ||--o{ TRANSACTIONS : owns
    CATEGORIES ||--o{ TRANSACTIONS : classifies
    USERS ||--o{ MONTHLY_RESERVES : owns
    MONTHLY_RESERVES ||--o{ MONTHLY_RESERVE_ENTRIES : groups
    USERS ||--o{ RESERVE_ACCOUNTS : owns
    RESERVE_ACCOUNTS ||--o{ RESERVE_ACCOUNT_ENTRIES : snapshots
    USERS ||--o{ CARDS : owns
    USERS ||--o{ CARD_CATEGORIES : owns
    USERS ||--o{ CARD_PURCHASES : owns
    CARDS ||--o{ CARD_PURCHASES : records
    CARD_CATEGORIES ||--o{ CARD_PURCHASES : classifies
    CARD_PURCHASES ||--|{ CARD_INSTALLMENTS : generates
    USERS ||--o{ CARD_INSTALLMENTS : scopes
    CARDS ||--o{ CARD_INSTALLMENTS : aggregates
    CARD_CATEGORIES ||--o{ CARD_INSTALLMENTS : aggregates

    USERS {
        bigint id PK
        string name
        string email UK
        string password
    }
    CATEGORIES {
        bigint id PK
        bigint user_id FK
        string name
        enum type
    }
    TRANSACTIONS {
        bigint id PK
        bigint user_id FK
        bigint category_id FK
        string description
        decimal amount
        enum type
        enum status
        string competency
        boolean is_recurring
    }
    MONTHLY_RESERVES {
        bigint id PK
        bigint user_id FK
        string competency
        decimal investimento
        text observations
    }
    MONTHLY_RESERVE_ENTRIES {
        bigint id PK
        bigint monthly_reserve_id FK
        string description
        decimal amount
    }
    RESERVE_ACCOUNTS {
        bigint id PK
        bigint user_id FK
        string name
        boolean active
    }
    RESERVE_ACCOUNT_ENTRIES {
        bigint id PK
        bigint reserve_account_id FK
        string competency
        decimal balance
        text note
    }
    CARDS {
        bigint id PK
        bigint user_id FK
        string name
        string responsible_person
        boolean active
    }
    CARD_CATEGORIES {
        bigint id PK
        bigint user_id FK
        string name
        boolean active
    }
    CARD_PURCHASES {
        bigint id PK
        bigint user_id FK
        bigint card_id FK
        bigint card_category_id FK
        decimal total_amount
        date purchase_date
        string reference_competency
        enum payment_type
        integer installments_total
        integer starting_installment_number
    }
    CARD_INSTALLMENTS {
        bigint id PK
        bigint card_purchase_id FK
        bigint user_id FK
        bigint card_id FK
        bigint card_category_id FK
        enum payment_type
        integer installment_number
        string competency
        decimal amount
    }
~~~

Categorias de cartão são independentes de categorias financeiras. Parcelas repetem usuário, cartão e categoria para agregação. Uma compra gera ao menos uma parcela. Restrições e valores exatos devem ser confirmados nas migrations atuais.

## Regras comuns de entrada, autorização e resposta

- Competências usam YYYY-MM e CompetencyRule valida formato e mês.
- Exceção observada: as rotas individuais de snapshot /reserve-accounts/{id}/entries/{competency} restringem apenas o formato YYYY-MM na rota e não aplicam CompetencyRule ao mês. A listagem com filtro e a gravação em lote validam formato e mês. Este desvio do AGENTS.md é documentado sem alteração do código.
- Rotas de cadastro/login são públicas; as demais rotas listadas em routes/api.php estão sob auth:sanctum.
- O user_id de gravações vem da identidade autenticada, não do body.
- Repositories e Services limitam consultas e mutações ao usuário. IDs de relações são verificados para o mesmo usuário e regras do módulo.
- Responses da API são JSON. Criação normalmente retorna 201, consulta/atualização 200, exclusão 204, e validação inválida 422. Alguns erros de domínio retornam 404 ou 422 conforme o Controller.
- Form Requests fazem validação de borda; Services verificam regras de domínio não expressas apenas pela validação declarativa.

## Módulos e interdependências

### Categorias e transações

Categorias gerais aceitam income e expense e são únicas por usuário, nome e tipo. Transações exigem uma categoria do mesmo usuário e cujo tipo coincida com o tipo da transação. A atualização de categoria é parcial; a alteração do tipo não reclassifica transações existentes.

~~~mermaid
flowchart TD
    CategoryPage["Categorias gerais"] --> CategoryService["CategoryService"]
    CategoryService --> CategoryTable[("categories")]
    TransactionPage["Transações"] --> TransactionServiceVue["transactionService"]
    TransactionServiceVue --> TransactionAPI["/api/transactions?competency=YYYY-MM"]
    CategoryServiceVue["categoryService"] --> TransactionPage
    TransactionAPI --> Request["Form Request"]
    Request --> Controller["TransactionController"]
    Controller --> Domain["TransactionService"]
    Domain --> CheckCategory{"Categoria do usuário e tipo compatível?"}
    CheckCategory -->|"não"| Invalid["Erro de domínio; resposta 422"]
    CheckCategory -->|"sim"| TransactionRepo["TransactionRepository"]
    TransactionRepo --> TransactionTable[("transactions")]
    TransactionRepo --> CategoryTable
    Domain --> FinancialEvent["DadosFinanceirosAlterados"]
    FinancialEvent --> FinancialCache["Invalida versão do dashboard geral"]
    TransactionTable --> Dashboard["Dashboard financeiro geral"]
    ReserveTables["Snapshots e investimento mensal"] --> Dashboard
    CardTables["Parcelas de cartão"] -. "não integram" .-> Dashboard
~~~

A listagem de transações exige competência. Status paid e pending entram nos agregados. is_recurring não gera transações futuras. Categorias gerais não disparam evento de invalidação do dashboard; nomes cacheados podem permanecer até o TTL.

### Dashboard financeiro geral

O DashboardController expõe resumo mensal, comparação de categorias, evolução, comparação entre competências e analytics. Analytics aceita janela de 2 a 12 meses, padrão 6. O dashboard geral agrega transações, snapshots das contas ativas e investimento mensal. Não consulta card_installments.

Para usuário u e competência c:

~~~text
Receitas(c) = soma das transações income em c
Despesas(c) = soma das transações expense em c
SaldoDoMês(c) = Receitas(c) - Despesas(c)

SaldoVigente(conta, c) = último balance com competência <= c
SaldoVigente(conta, c) = 0 no agregado se não há snapshot anterior ou atual
ReservaBase(c) = soma dos saldos vigentes das contas atualmente ativas
ReservaAtual(c) = ReservaBase(c) + SaldoDoMês(c)
Investimento(c) = monthly_reserves.investimento ou 0
TotalGuardado(c) = ReservaAtual(c) + Investimento(c)
~~~

O status atual da conta determina se ela entra em todas as competências consultadas, inclusive históricas. previous_reserve é uma soma calculada, não um campo persistido nem necessariamente o fechamento real do mês anterior. Como snapshots não são movimentos, a fórmula pode contar saldo duas vezes se a declaração já inclui a sobra.

Analytics inclui evolução, fluxo, ranking de despesas, participação por categoria, heatmap, insights e KPIs. As médias usam todos os meses da janela, inclusive vazios. best_month e worst_month são o maior e o menor remaining_amount. income_commitment_rate é despesa/entrada × 100; savings_rate é saldo restante/entrada × 100; ambos são zero quando a entrada é menor ou igual a zero. projected_closing é somente o saldo restante da competência selecionada.

A comparação mensal usa diferença do valor atual menos o anterior. Para variações percentuais em que o anterior é menor ou igual a zero, o resultado é 100 quando o atual é positivo e 0 nos demais casos. A participação por categoria divide o total da categoria pelo total de despesas. O heatmap classifica participações a partir de 40% como critical, 25% como warning, 12% como attention e o restante como safe.

### Reserva mensal, contas e investimentos

Contas de reserva e reserva mensal são entidades distintas:

- ReserveAccount é nome e estado ativo/inativo.
- ReserveAccountEntry registra um snapshot balance por competência e conta, não um delta.
- Sem snapshot na competência, a consulta herda o snapshot mais recente anterior. A resposta da conta apresenta saldo atual, saldo da competência anterior, delta e is_inherited.
- MonthlyReserve é única por usuário e competência e mantém observations e investimento.
- MonthlyReserveEntry armazena itens de investimento. Criar, editar ou excluir item recalcula investimento pela soma dos itens em transação com bloqueio do pai.
- A API ainda aceita gravação direta do campo investimento; essa compatibilidade pode deixá-lo divergente da soma dos itens até a próxima mutação de item.
- PUT /reserve-account-balances aceita vários snapshots na mesma competência e grava tudo em transação. A tela envia apenas contas ativas e saldos alterados. Renomeações são requests separados.
- Contas arquivadas continuam com histórico acessível. O filtro active é aplicado ao cálculo do dashboard pelo estado atual, inclusive retrospectivamente.

As migrations migraram valores históricos do antigo campo reserva_anterior para uma conta de reserva e removeram a coluna em migration posterior. O campo não existe no modelo atual.

### Cartões

Cards e CardCategory têm estado active. Listagens de gerenciamento podem incluir inativos com include_inactive; formulários de compra enviam somente itens ativos. Compras antigas continuam consultáveis após arquivamento. Exclusão de cartão/categoria com compras vinculadas é bloqueada; a interface orienta arquivar.

CardPurchase armazena cabeçalho da compra. CardInstallment contém parcelas derivadas, e os relatórios mensais somam parcelas. Para cash, o Service normaliza total de parcelas e parcela inicial para 1. Para installment, o total é limitado a 60; a atualização exige os campos completos e substitui todas as parcelas na mesma transação.

#### Distribuição de parcelas

~~~text
totalCentavos = round(total_amount × 100)
baseCentavos = intdiv(totalCentavos, quantidade)
resto = totalCentavos mod quantidade

Para cada número i entre starting_installment_number e installments_total:
    valorCentavos(i) = baseCentavos + 1 se i > installments_total - resto
    competência(i) = reference_competency + (i - starting_installment_number) meses
~~~

O resto em centavos é distribuído às últimas parcelas do cronograma completo. Se a compra começa em K > 1, somente K..N é persistido, mantendo a divisão como se N parcelas tivessem sido calculadas. A competência de referência corresponde à primeira parcela persistida. O helper TypeScript exibe uma prévia; o backend é autoridade dos valores gravados.

O campo installment_schedule_ended significa que a última competência prevista chegou ou passou. is_settled permanece por compatibilidade e não atesta pagamento. Não há ciclo de fatura, vencimento, quitação, pagamento parcial ou estorno.

O dashboard de cartões soma card_installments e tem cache próprio. committed_future inclui parcelas com competência futura de qualquer payment_type; outstanding_balance restringe a payment_type installment. Competências sem compras contam como zero nas médias históricas. Os dashboards geral e de cartões não compartilham fontes, cálculos nem versão de cache.

## Endpoints da API

Todas as rotas usam o prefixo /api. Register e login são públicos; as demais listadas exigem auth:sanctum. Métodos exatos estão em routes/api.php.

| Área | Endpoints principais | Parâmetros/observações |
|---|---|---|
| Autenticação | POST /auth/register, POST /auth/login, POST /auth/logout | Logout protegido; cadastro/login têm throttles próprios. |
| Categorias | GET/POST /categories; GET/PUT/PATCH/DELETE /categories/{id} | Categoria geral por usuário. |
| Transações | GET/POST /transactions; GET/PUT/PATCH/DELETE /transactions/{id} | GET exige competency válida. |
| Reserva mensal | GET/POST /monthly-reserves; GET/PUT/PATCH/DELETE /monthly-reserves/{id}; GET/POST /monthly-reserves/{id}/entries; PUT/PATCH/DELETE /monthly-reserves/{id}/entries/{entry} | Os itens são investimentos. |
| Contas de reserva | GET/POST /reserve-accounts; PUT/PATCH /reserve-accounts/{id}; GET /reserve-accounts/{id}/entries; PUT/DELETE /reserve-accounts/{id}/entries/{YYYY-MM}; PUT /reserve-account-balances | GET aceita competency e include_archived. Não há show nem delete para a conta. |
| Dashboard geral | GET /dashboard/analytics, /monthly-summary, /category-comparison, /monthly-evolution, /month-comparison | analytics months de 2 a 12, padrão 6. |
| Cartões | CRUD /cards, /card-categories e /card-purchases | Listas de cartões/categorias aceitam include_inactive; compras aceitam competency, card_id, card_category_id e payment_type. |
| Dashboard de cartões | GET /card-dashboard/analytics, /card-dashboard/monthly-summary | analytics months de 2 a 24, padrão 6. |

As rotas apiResource incluem show e PUT/PATCH para update. Use rotas e Requests como referência para parâmetros obrigatórios e formatos completos.

## Cache e eventos

~~~mermaid
flowchart TB
    TxWrite["Transação"] --> FinanceEvent["DadosFinanceirosAlterados(user_id)"]
    ReserveWrite["Conta, snapshot, reserva ou item de investimento"] --> FinanceEvent
    CardWrite["Cartão, categoria de cartão ou compra"] --> CardEvent["CartaoDadosAlterados(user_id)"]

    FinanceEvent --> FinanceListener["InvalidarCacheDashboard"]
    CardEvent --> CardListener["InvalidarCacheCartoes"]
    FinanceListener --> FinanceVersion["dashboard:user:{id}:version incrementa"]
    CardListener --> CardVersion["cards:user:{id}:version incrementa"]

    FinanceVersion --> FinanceKey["dashboard:analytics:user:{id}:{competency}:{months}:v{version}"]
    CardVersion --> CardKey["cards:analytics:user:{id}:{competency}:{months}:v{version}"]
    FinanceKey --> FinanceTTL["Cache::remember: cinco minutos"]
    CardKey --> CardTTL["Cache::remember: cinco minutos"]
~~~

Mutações de transações, reservas e snapshots emitem DadosFinanceirosAlterados. Categorias financeiras gerais não emitem esse evento. Mutações de cartões, categorias de cartão e compras emitem CartaoDadosAlterados. Cada analytics usa usuário, competência, janela e versão na chave. O resumo mensal não usa o cache analítico.

## Arquivos de referência

| Área | Arquivos principais |
|---|---|
| Rotas e bootstrap | routes/api.php, routes/web.php, bootstrap/app.php |
| Sessão | AuthController, AuthService, RegisterRequest, LoginRequest, authStore.ts, httpClient.ts |
| Categorias e transações | CategoryService, TransactionService, seus Repositories, Requests e Resources |
| Reserva | MonthlyReserveService, ReserveAccountService, repositories, models e migrations |
| Dashboard geral | DashboardService, FinancialAnalyticsService, CategoryAnalyticsService, MonthlyComparisonService |
| Cartões | CardPurchaseService, CardDashboardService, CardAnalyticsService, CardInstallmentRepository |
| Cache | DadosFinanceirosAlterados, CartaoDadosAlterados e listeners de invalidação |
| Persistência | database/migrations |
| Frontend | frontend/src/pages, services, stores e router |