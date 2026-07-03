# CLAUDE.md — API Gestão Financeira

## LEITURA OBRIGATÓRIA ANTES DE QUALQUER AÇÃO

Esta instrução se aplica a **todo e qualquer pedido** feito no chat ou no terminal — sem exceção.

Antes de criar, alterar, remover, mover ou refatorar qualquer arquivo, leia os arquivos abaixo na ordem indicada.

---

### 1. Sempre — toda ação (backend ou frontend)

| # | Arquivo | Por quê |
|---|---------|---------|
| 1 | `.ai/ai-rules.md` | Regras gerais e ordem de prioridade |
| 2 | `.ai/project-context.md` | Stack, modelos de dados, endpoints |
| 3 | `.ai/feature-index.md` | Índice de funcionalidades e caminhos dos contexts |

---

### 2. Alterações no Backend (`app/`, `routes/`, `database/`, `config/`)

| # | Arquivo | Por quê |
|---|---------|---------|
| 4 | `.ai/backend/LEIA-ME.md` | Regras obrigatórias específicas do backend |
| 5 | `.ai/backend/architecture.md` | DDD, camadas, fluxo de dependências |
| 6 | `.ai/backend/coding-standards.md` | Padrões de código PHP/Laravel |
| 7 | Context da funcionalidade em `.ai/backend/contexts/` | Fluxos, regras de negócio, arquivos envolvidos |

---

### 3. Alterações no Frontend (`frontend/`)

| # | Arquivo | Por quê |
|---|---------|---------|
| 4 | `.ai/frontend/LEIA-ME.md` | Regras obrigatórias específicas do frontend |
| 5 | `.ai/frontend/overview.md` | Estrutura, componentes, stores, roteamento |
| 6 | `.ai/frontend/coding-standards.md` | Padrões de código Vue 3 / Tailwind |
| 7 | Arquivo específico em `.ai/frontend/` da área envolvida | Contexto detalhado (componentes, pages, etc.) |

---

## REGRAS OBRIGATÓRIAS

1. **Explique antes de executar** — informe o que será feito, o objetivo, os arquivos que serão alterados e os impactos.
2. **Sempre pergunte:** _"Deseja que eu execute esta alteração?"_ — nunca assuma autorização.
3. **Nunca altere regra de negócio, apague, mova ou renomeie arquivos sem autorização explícita.**
4. **Após qualquer alteração**, apresente resumo com: arquivos modificados, funcionalidades afetadas e próximos passos sugeridos.

---

## Estrutura de Contexto

```
.ai/
├── backend/                ← regras e contextos do backend (Laravel / PHP)
│   ├── LEIA-ME.md
│   ├── architecture.md
│   ├── coding-standards.md
│   └── contexts/
│       ├── auth.md
│       ├── transactions.md
│       ├── categories.md
│       └── dashboard.md
├── frontend/               ← regras e contextos do frontend (Vue 3 / Pinia / Tailwind)
│   ├── LEIA-ME.md
│   ├── coding-standards.md
│   ├── overview.md
│   └── *.md
├── ai-rules.md             ← regras gerais e ordem de prioridade
├── project-context.md      ← contexto geral, stack, modelos, endpoints
└── feature-index.md        ← índice de todas as funcionalidades
```
