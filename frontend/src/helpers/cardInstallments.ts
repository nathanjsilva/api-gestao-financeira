/**
 * Replica no frontend o mesmo algoritmo de arredondamento do backend
 * (CardPurchaseService::calcularParcelas) para exibir um preview fiel das
 * parcelas antes de salvar a compra.
 */
export function calculateInstallments(totalAmount, installmentsTotal, startingInstallmentNumber, referenceCompetency) {
  const total = Number(totalAmount) || 0
  const installmentsCount = Number(installmentsTotal) || 1
  const startingNumber = Number(startingInstallmentNumber) || 1

  if (installmentsCount < 1 || startingNumber < 1 || startingNumber > installmentsCount) {
    return []
  }

  if (!/^\d{4}-\d{2}$/.test(String(referenceCompetency || ''))) {
    return []
  }

  const totalCents = Math.round(total * 100)
  const baseCents = Math.floor(totalCents / installmentsCount)
  const remainder = totalCents % installmentsCount

  const [year, month] = referenceCompetency.split('-').map(Number)
  const installments = []

  for (let number = startingNumber; number <= installmentsCount; number++) {
    const cents = baseCents + (number > installmentsCount - remainder ? 1 : 0)
    const offset = number - startingNumber
    const date = new Date(year, month - 1 + offset, 1)
    const competency = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`

    installments.push({
      installment_number: number,
      competency,
      amount: cents / 100,
    })
  }

  return installments
}
