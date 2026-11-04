# API Gestão Financeira

Aplicação multiusuário de finanças pessoais. O repositório contém uma API REST em Laravel e uma SPA independente em Vue 3 com TypeScript.

## Componentes

| Área | Implementação atual |
|---|---|
| API | Laravel 13, Sanctum e Eloquent; Composer exige PHP ^8.3 e a imagem Docker atual usa PHP 8.4 com Apache |
| SPA | Vue 3, TypeScript, Vue Router, Pinia, Axios, Tailwind CSS 4, ApexCharts |
| Banco do ambiente Docker | MySQL 8 |
| Cache | Cache Laravel configurável, com database como padrão; o Compose também oferece Redis |
| Execução local | API em localhost:8000; Vite em localhost:5173 |

A SPA está em frontend/ e não é servida pela rota web do Laravel. A rota / retorna a view welcome. O Docker Compose inicia API, MySQL e Redis, mas não inicia o Vite nem publica frontend/dist.

## Módulos implementados

- Autenticação e sessão por token Sanctum.
- Categorias financeiras de entrada e saída.
- Transações por competência mensal.
- Contas de reserva com snapshots de saldo e herança entre competências.
- Reserva mensal com itens de investimento.
- Dashboard financeiro geral.
- Cartões, categorias próprias de cartão, compras à vista ou parceladas e dashboard de cartões independente.

## Arquitetura

O fluxo padrão da API é:

~~~text
rota → middleware → Form Request → Controller → Service → Repository → Model
                                                               ↓
                                                         banco relacional

Controller → Resource ou resposta JSON direta
~~~

Controllers coordenam HTTP e delegam regras a Services. Services coordenam o domínio; Repositories concentram consultas Eloquent; Resources controlam a representação pública. Cadastro e login são rotas públicas. As demais rotas da API exigem auth:sanctum.

## Regras importantes

- A competência usa YYYY-MM e é validada no backend por CompetencyRule.
- Categorias e registros financeiros pertencem ao usuário autenticado. O cliente não define user_id.
- Uma transação depende de categoria do mesmo usuário e do mesmo tipo.
- Transações pagas e pendentes entram nos agregados financeiros; is_recurring é apenas um marcador e não cria meses futuros.
- ReserveAccountEntry é snapshot de saldo, não movimento. Na ausência de snapshot na competência, usa-se o último anterior.
- MonthlyReserve.investimento pode ser enviado diretamente pela API. Alterações em itens de investimento recalculam o total como soma dos itens.
- Compras de cartão geram CardInstallment. Os painéis de cartões somam parcelas, não total_amount da compra.
- Dados de cartão não alimentam o dashboard financeiro geral.
- Os dois dashboards têm cache e invalidação separados, com chaves por usuário.

As fórmulas e limitações contábeis estão em docs/fluxograma-arquitetura-aplicacao.md e nos contextos em .ai/backend/contexts/.

## Início local

Com Docker:

~~~bash
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan migrate
~~~

Em outro terminal, inicie a SPA:

~~~bash
cd frontend
npm ci
npm run dev
~~~

Configure frontend/.env com VITE_API_BASE_URL apontando para a API, por exemplo http://localhost:8000/api. O valor é incorporado no build do Vite. Não coloque segredos em variáveis VITE_.

## Comandos disponíveis

Na raiz, os comandos de Laravel são definidos em composer.json; a suíte pode ser iniciada com composer test.

Em frontend/:

- npm run dev inicia Vite na porta 5173.
- npm run build compila a SPA.
- npm run typecheck executa tsc --noEmit. Esse script não executa vue-tsc, portanto não verifica integralmente os blocos TypeScript dos componentes Vue.
- npm run preview inicia a prévia do build na porta 4173.

Consulte os arquivos de configuração para os comandos e versões atuais. Esta documentação não afirma que verificações foram executadas.

## Documentação

- [Fluxo, arquitetura, dados, endpoints e fórmulas](docs/fluxograma-arquitetura-aplicacao.md)
- [Auditoria funcional e limites atuais](docs/auditoria-funcional-financeira.md)
- [Configuração atual do frontend](docs/guia-configuracao-vue-typescript-vite.md)
- [Requisitos de implantação em VM](docs/deploy-oracle-cloud.md)
- [Mapa de módulos backend](.ai/project-context.md)
- [Mapa do frontend](.ai/frontend/overview.md)
- [Instruções normativas do projeto](AGENTS.md)