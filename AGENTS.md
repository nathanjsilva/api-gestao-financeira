# AGENTS.md — Instruções universais do projeto

Este arquivo contém as instruções normativas para qualquer pessoa ou ferramenta de IA que analise, desenvolva, mantenha, refatore, teste ou revise este repositório. As regras não dependem de um modelo ou ferramenta específica.

## Fonte de verdade e prioridade

As regras de trabalho, arquitetura, segurança, qualidade, testes e revisão ficam centralizadas neste arquivo. O CLAUDE.md é apenas um direcionador para estas instruções. Os arquivos de orientação normativa em .ai apontam para este documento; não mantenha cópias divergentes das regras neles.

Os documentos de .ai/project-context.md, .ai/feature-index.md, .ai/backend/contexts/ e .ai/frontend/ contêm contexto técnico e funcional detalhado. Consulte-os para entender modelos, fluxos e invariantes de cada funcionalidade. Não duplique detalhes de uma funcionalidade neste arquivo quando a referência específica for suficiente; mantenha aqui as regras universais e transversais.

Em caso de divergência, aplique esta prioridade:

1. Segurança e privacidade dos dados.
2. Integridade dos dados e regras de negócio aprovadas.
3. Contratos de API e arquitetura existentes.
4. Padrões de código e convenções descritos neste arquivo.
5. Experiência do usuário, incluindo estados de carregamento e mensagens de erro claras.

Se um documento de contexto estiver desatualizado ou contradizer o código, as configurações, as migrations ou os testes, confira a implementação atual. Não altere regras de negócio com base em suposição; explique a divergência e peça esclarecimento quando ela afetar a decisão.

## Leitura obrigatória antes do trabalho

Antes de qualquer alteração em código, testes, configuração ou documentação, leia os documentos abaixo nesta ordem:

### Para toda tarefa

1. .ai/ai-rules.md
2. .ai/project-context.md
3. .ai/feature-index.md

### Para tarefas de backend

4. .ai/backend/LEIA-ME.md
5. .ai/backend/architecture.md
6. .ai/backend/coding-standards.md
7. O contexto da funcionalidade em .ai/backend/contexts/

### Para tarefas de frontend

4. .ai/frontend/LEIA-ME.md
5. .ai/frontend/overview.md
6. .ai/frontend/coding-standards.md
7. O documento específico da área em .ai/frontend/, quando existir

Leia também os arquivos de código, testes, rotas e configurações diretamente envolvidos. Verifique a estrutura atual antes de confiar em nomes de arquivos ou exemplos de documentação. Para tarefas que envolvam as duas camadas, siga as duas listas.

O índice atual de contextos inclui autenticação, categorias, transações, reserva mensal, dashboard e cartões em .ai/backend/contexts/, além do contexto de cartões em .ai/frontend/cards.md. Consulte apenas os contextos relevantes para a alteração.

## Processo de trabalho e autorização

Antes de alterar arquivos, descreva ao usuário o que será feito, o objetivo, os arquivos afetados e os impactos esperados. Pergunte: “Deseja que eu execute esta alteração?”. Aguarde autorização explícita antes de executar a alteração.

Nunca presuma autorização para apagar, mover ou renomear arquivos, mudar regras de negócio, alterar dados existentes, modificar contratos incompatíveis ou fazer mudanças destrutivas. Faça a menor alteração que resolva o pedido e preserve trabalho não relacionado que já exista no workspace.

Após qualquer alteração, informe:

- Arquivos modificados.
- Funcionalidades ou áreas afetadas.
- Verificações realizadas e seus resultados; deixe claro quando não foram executadas.
- Próximos passos sugeridos, se houver.

## Stack e organização do projeto

Confirme versões e comandos nos arquivos de configuração da área afetada. A estrutura atual inclui:

- Backend: Laravel 13, PHP 8.3, API REST, Sanctum, Eloquent e MySQL 8 em Docker ou SQLite em ambiente local; Redis é usado no ambiente Docker.
- Frontend: SPA em Vue 3, Composition API, TypeScript, Pinia, Vue Router, Axios, Tailwind CSS 4 e Vite. A aplicação Vue está em frontend/; consulte frontend/package.json para comandos e dependências dessa aplicação.
- Testes do backend: PHPUnit por meio da infraestrutura Laravel.

O frontend atual contém arquivos TypeScript (.ts) e componentes Vue com suporte a TypeScript. Alguns documentos de contexto ainda mostram caminhos .js e exemplos JavaScript. Para novas alterações, confirme os arquivos e configurações reais e preserve o uso de TypeScript já estabelecido; não converta código para JavaScript por seguir um exemplo antigo.

A organização principal do backend está em app/Http, app/Models, app/Repositories, app/Rules e app/Services. O frontend organiza páginas, componentes, serviços, stores, composables, helpers, constantes, middleware, router e layouts em frontend/src/.

## Arquitetura e responsabilidades

### Backend

O fluxo padrão é: rota → Form Request e Controller → Service → Repository → Model Eloquent. O Controller usa um Resource para formatar a resposta da API.

- Rotas declaram endpoints e middleware. Rotas protegidas usam auth:sanctum.
- O middleware global ForceJsonResponse mantém respostas da API em JSON. Preserve os limites de throttle existentes nas rotas de cadastro e autenticação.
- Form Requests validam e autorizam a entrada antes de ela alcançar o fluxo de negócio.
- Controllers recebem a requisição, extraem apenas dados validados e a identidade autenticada, delegam ao Service e retornam Resource ou resposta HTTP. Não devem conter queries Eloquent nem regras de negócio.
- Services concentram as regras de negócio e coordenam operações entre repositórios. Recebem dados simples, não objetos Request ou Response.
- Repositories concentram o acesso a dados via Eloquent. Não devem duplicar regras de negócio.
- Models descrevem a estrutura Eloquent, relações, casts e campos atribuíveis. Mantenha-os focados.
- Resources definem explicitamente a representação JSON exposta pela API.

Use injeção de dependência por construtor. Prefira propriedades readonly quando compatíveis com a classe e o padrão existente. Use eager loading quando necessário para evitar consultas N+1.

DashboardService coordena os endpoints analíticos financeiros. FinancialAnalyticsService, CategoryAnalyticsService e MonthlyComparisonService apoiam o dashboard financeiro; CardDashboardService e CardAnalyticsService pertencem ao módulo de cartões e permanecem independentes. Consulte o contexto do módulo para os fluxos e os dados retornados.

Todas as respostas da API são JSON. Preserve os contratos e os códigos HTTP existentes: normalmente 200 para consulta e atualização, 201 para criação, 200 ou 204 para exclusão, 401 para não autenticado, 404 para recurso inexistente ou sem acesso e 422 para validação inválida. Confirme o comportamento da rota e do recurso antes de alterá-los.

### Frontend

- Use Vue 3 com Composition API e script setup, mantendo o padrão TypeScript já usado nos arquivos atuais.
- Páginas representam telas/rotas; componentes são reutilizáveis e evitam concentrar lógica de negócio. Extraia comportamento compartilhado para composables ou services quando isso seguir a organização existente.
- Faça chamadas HTTP pelos services do módulo, usando o cliente compartilhado httpClient para manter interceptors de autenticação e tratamento de 401. Não use Axios diretamente em páginas ou componentes.
- Use Pinia para estado global conforme o padrão atual do projeto, hoje concentrado na sessão/autenticação. Não introduza estado global para estado local de tela sem necessidade.
- Não acesse localStorage diretamente em páginas ou componentes; use o authStore para a sessão.
- Reutilize componentes em frontend/src/components/base/ e demais componentes compartilhados antes de criar controles duplicados.
- Use os componentes base de formulário, como BaseInput, BaseSelect, BaseTextarea e BaseButton, em vez de recriar controles equivalentes.
- Declare props e eventos explicitamente, com os tipos de TypeScript apropriados. Mantenha a ordem e a organização de imports, props/emits, stores/composables, estado, computados, métodos e ciclo de vida conforme os componentes existentes.
- Use as constantes de nomes de rota e os guards existentes. Não espalhe strings de rotas pelo código.
- Use useLoading e useFormErrors nos formulários quando aplicável. Durante submissões, mostre carregamento, desabilite o envio e apresente erros gerais e por campo.
- Use helpers existentes para moeda, percentual, competência e erros da API. Formate valores em BRL com formatCurrency(), percentuais com formatPercentage() e obtenha a competência com getCurrentCompetency(); a competência segue YYYY-MM.
- Prefira classes Tailwind e os tokens existentes. Evite CSS próprio, estilos inline, cores ou espaçamentos arbitrários quando houver utilitário ou padrão existente.
- Mantenha estados de carregamento, erro e vazio claros e acessíveis.

## Segurança e privacidade

O isolamento entre usuários é obrigatório e deve ser garantido no backend em cada operação:

- Toda consulta, atualização e exclusão de dado pertencente a um usuário deve ser limitada ao identificador do usuário autenticado.
- Obtenha o identificador pelo contexto de autenticação, como request->user()->id. Nunca confie em user_id enviado no body, query string ou outro campo controlado pelo cliente.
- Ao aceitar IDs de relações, valide no backend que os registros relacionados pertencem ao mesmo usuário e são compatíveis com a operação.
- Proteja rotas autenticadas com auth:sanctum. Autorização não pode depender apenas de guards, botões ou validações no frontend.
- Valide toda entrada com Form Requests e use somente dados validados. Use Resources para controlar os dados de saída.
- Preserve fillable, chaves estrangeiras, índices e restrições de unicidade. Use consultas parametrizadas do Eloquent/query builder; não concatene entrada do usuário em SQL.
- Não exponha credenciais, tokens, dados pessoais ou informações financeiras em logs, mensagens de erro, respostas ou arquivos versionados. Segredos ficam em variáveis de ambiente.
- Não inclua dd() ou dump() no backend, nem console.log() no frontend destinado à produção.

## Integridade de dados, domínio e contratos

- Não mude uma regra de negócio sem autorização explícita.
- Nunca edite uma migration existente. Para mudanças de schema, crie uma nova migration, com método down() que permita rollback. Não remova campo ou dado existente sem uma migração compatível e autorização.
- Preserve os valores e formatos de domínio já usados: competência YYYY-MM; tipos financeiros income e expense; status de transação paid e pending; pagamento de cartão cash e installment. Confirme o contexto específico antes de alterar qualquer regra.
- A competência é validada por App\Rules\CompetencyRule no backend. Use o helper de competência existente no frontend.
- Preserve o isolamento do módulo de cartões em relação às categorias e transações financeiras gerais. Não acople os cálculos ou o cache dos dois dashboards sem uma mudança aprovada e análise de impacto.
- Os analytics do dashboard usam cache de cinco minutos. Ao alterar dados que afetam analytics, verifique a invalidação; as chaves precisam manter o isolamento por usuário e competência. O dashboard de cartões tem fluxo e invalidação próprios.
- Não mude nomes de rotas, formato de requests/responses ou interfaces de Services sem verificar todos os consumidores e atualizar as partes correspondentes do frontend, testes e documentação.
- Use transações de banco quando uma operação de negócio gravar múltiplos registros que precisem permanecer consistentes.

## Clean Code, estilo e nomenclatura

Escreva código claro, coeso e simples de manter:

- Dê a cada classe, função ou componente uma responsabilidade clara. Mantenha métodos pequenos e com nomes que expressem a intenção.
- Evite duplicação; antes de criar helpers, componentes, serviços ou constantes, procure os equivalentes existentes.
- Prefira soluções simples e alinhadas à arquitetura atual. Não adicione abstrações, dependências ou refatorações amplas que não sejam necessárias ao pedido.
- Use tipos explícitos em parâmetros, retornos, props e dados relevantes. Evite valores mágicos e estados implícitos.
- Em PHP, declare tipos de parâmetros e retornos e use recursos da versão configurada quando simplificarem o código sem reduzir a clareza.
- Trate erros de forma previsível e apresente mensagens compreensíveis, sem ocultar falhas silenciosamente.
- Lance exceções de domínio apropriadas a partir de Services para falhas de regra de negócio; não converta falhas em respostas de sucesso.
- Siga PSR-12 no PHP e use Laravel Pint conforme a configuração do projeto. Respeite a formatação e o estilo já usados nos arquivos próximos.

Convenções de nomenclatura:

| Elemento | Convenção |
|---|---|
| Classes PHP, componentes Vue e páginas | PascalCase; páginas terminam em Page |
| Métodos e variáveis PHP | camelCase, preferindo termos em português conforme os padrões existentes |
| Tabelas | snake_case no plural |
| Colunas | snake_case |
| Rotas HTTP | kebab-case |
| Arquivos de componentes | PascalCase.vue |
| Composables | camelCase com prefixo use |
| Services, stores e helpers do frontend | camelCase, mantendo o sufixo e a organização já usados |
| Props | camelCase |
| Eventos emitidos | kebab-case |

No backend, mantenha os sufixos e nomes de classe já estabelecidos: Controller, Service, Repository, Request e Resource. Requests de escrita usam verbos como Store e Update; métodos de Services e Repositories usam nomes descritivos, preferencialmente em português.

## Testes e verificações

Ao mudar comportamento, corrigir um defeito ou alterar um contrato:

- Crie ou atualize testes que cubram o comportamento alterado e os casos de regressão relevantes.
- Para endpoints com dados de usuário, teste também isolamento: um usuário não pode consultar, modificar ou excluir os registros de outro.
- Cubra validação, autorização, respostas e limites relevantes do domínio.
- Para fluxos frontend, verifique estados de carregamento, erro, vazio e sucesso; adicione testes se houver infraestrutura de testes para a área.
- Execute verificações focadas e, quando apropriado, a suíte da área. Confira scripts atuais antes de assumir comandos.
- Comandos atuais incluem composer test para a suíte Laravel e, em frontend/, npm run typecheck e npm run build. O frontend/package.json deve ser consultado para confirmar os scripts.
- Se uma verificação não puder ser executada, informe o motivo. Nunca declare um teste aprovado sem executá-lo.

## Revisão obrigatória antes de concluir

Revise o diff e confirme:

1. A alteração corresponde ao pedido e não inclui mudanças não relacionadas.
2. A arquitetura e a nomenclatura seguem os padrões da área.
3. Autenticação, autorização, validação e isolamento por usuário estão corretos.
4. Migrations, integridade dos dados, transações e cache foram considerados quando aplicáveis.
5. Contratos de API, rotas e consumidores frontend continuam compatíveis ou foram atualizados juntos.
6. Testes e verificações adequados foram executados, ou a limitação foi informada.
7. Não ficaram segredos, logs de depuração ou documentação contraditória.
