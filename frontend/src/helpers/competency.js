export function getCurrentCompetency(date = new Date()) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')

  return `${year}-${month}`
}

export function formatCompetencyLabel(competency) {
  const [year, month] = competency.split('-')
  const date = new Date(Number(year), Number(month) - 1, 1)
  const mes = date.toLocaleString('pt-BR', { month: 'short' })

  return `${mes.charAt(0).toUpperCase() + mes.slice(1)}/${year.slice(2)}`
}

