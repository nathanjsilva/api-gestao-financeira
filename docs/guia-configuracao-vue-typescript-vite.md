# Frontend Vue, TypeScript e Vite neste repositório

Este documento registra a configuração que existe em frontend/. As orientações normativas de implementação estão em AGENTS.md.

## Stack e configuração atuais

- Vue 3 e Composition API.
- TypeScript; os arquivos da aplicação usam .ts e componentes .vue com lang="ts".
- Vue Router para páginas e guards; Pinia para sessão.
- Axios pelo httpClient compartilhado.
- Vite 6 e plugin Vue; Tailwind CSS 4 via plugin do Vite.
- ApexCharts para os gráficos.

Arquivos centrais:

| Arquivo | Função |
|---|---|
| frontend/index.html | Entrada do Vite; carrega src/main.ts. |
| frontend/src/main.ts | Registra Pinia e router e monta App.vue. |
| frontend/src/router/routes.ts | Rotas e carregamento das páginas. |
| frontend/src/services/api/httpClient.ts | Axios compartilhado, Bearer token e tratamento de 401. |
| frontend/src/env.d.ts | Tipos para variáveis de ambiente do Vite. |
| frontend/tsconfig.json | Configuração TypeScript. |
| frontend/vite.config.ts | Plugins do Vue e Tailwind CSS. |
| frontend/package.json | Dependências e scripts. |

## Ambiente da API

O cliente lê VITE_API_BASE_URL. Se a variável não estiver definida, usa http://localhost:8000/api. Vite incorpora esse valor durante o build; mudar a variável depois do build não altera os arquivos gerados.

Exemplo local:

~~~env
VITE_API_BASE_URL=http://localhost:8000/api
~~~

Variáveis VITE_ são entregues ao navegador. Não coloque segredos nelas.

## Scripts atuais

Execute em frontend/:

~~~bash
npm ci
npm run dev
npm run typecheck
npm run build
~~~

- dev inicia o Vite na porta 5173.
- typecheck executa tsc --noEmit.
- build executa vite build.
- preview inicia a prévia do build na porta 4173.

vue-tsc está instalado, mas não é chamado pelos scripts atuais. Como o typecheck chama tsc, ele não valida integralmente blocos TypeScript dentro de arquivos .vue. O build do Vite também não substitui uma verificação de tipos Vue.

## Particularidades de TypeScript

frontend/tsconfig.json inclui arquivos .ts, .d.ts e .vue, mas define strict=false e allowJs=false. Não converta novos componentes ou serviços para JavaScript: preserve TypeScript e o padrão atual.

O entrypoint e as configurações são .ts. Referências a arquivos .js em exemplos de migração genérica não descrevem a estrutura atual deste repositório.

## Estrutura atual

~~~text
frontend/src/
├── components/
├── composables/
├── constants/
├── helpers/
├── layouts/
├── middleware/
├── pages/
├── router/
├── services/
└── stores/
~~~

Use services por módulo e o httpClient compartilhado para chamadas HTTP. Guards do frontend controlam navegação; a autorização de dados continua no backend.

## Referências

- [AGENTS.md](../AGENTS.md) — regras normativas.
- [Mapa de módulos frontend](../.ai/frontend/overview.md).
- [Módulo frontend de cartões](../.ai/frontend/cards.md).
- [Fluxos e contratos da aplicação](fluxograma-arquitetura-aplicacao.md).