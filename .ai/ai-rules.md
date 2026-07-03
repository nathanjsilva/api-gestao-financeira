# ai-rules.md — Regras Gerais e Prioridade

## Ordem de Prioridade

1. **Segurança** — nunca expor dados de outros usuários; todas as queries devem filtrar por `user_id`
2. **Integridade de dados** — nunca alterar regra de negócio sem autorização explícita
3. **Consistência de padrões** — seguir os padrões de arquitetura já estabelecidos no projeto
4. **Experiência do usuário** — feedback claro, loading states, mensagens de erro legíveis

---

## Regras Obrigatórias

### Antes de qualquer ação
- Leia `project-context.md` e `feature-index.md`
- Para backend: leia `.ai/backend/LEIA-ME.md`
- Para frontend: leia `.ai/frontend/LEIA-ME.md`
- Leia o context específico da funcionalidade envolvida

### Durante a implementação
- **Explique antes de executar**: descreva o que será feito, arquivos afetados e impactos
- **Pergunte antes de agir**: "Deseja que eu execute esta alteração?"
- **Nunca assuma autorização** para apagar, mover, renomear ou alterar regras de negócio
- **Nunca altere migrations existentes** — crie novas quando necessário

### Após qualquer alteração
Apresente resumo com:
- Arquivos modificados
- Funcionalidades afetadas
- Próximos passos sugeridos

---

## Padrões Globais

| Aspecto | Padrão |
|---------|--------|
| Competência | Formato `YYYY-MM` (ex: `2026-07`) |
| Tipos financeiros | `income` (entrada) / `expense` (saída) |
| Status de transação | `paid` (pago) / `pending` (pendente) |
| Autenticação | Bearer token (Sanctum) — header `Authorization` |
| Isolamento de dados | Todo acesso filtra por `user_id` do token autenticado |
| Cache | Dashboard analytics: 5 minutos |
| Moeda | BRL — `formatCurrency()` no frontend |

---

## O que NUNCA fazer

- Não expor dados de um usuário para outro
- Não alterar migrations já existentes e aplicadas
- Não remover campos de uma migration sem criar nova migration
- Não mudar nomes de rotas sem verificar impacto no frontend
- Não alterar a interface dos Services sem verificar Controllers dependentes
- Não mudar contratos de API sem atualizar o frontend correspondente
- Não usar `dd()` ou `dump()` em código de produção
- Não fazer `console.log` em código de produção no frontend
