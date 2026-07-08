const { chromium } = require('playwright-core');

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE = 'http://localhost:5173';
const shotDir = 'C:\\Users\\natha\\AppData\\Local\\Temp\\claude\\c--Projetos-api-gestao-financeira\\3b7adb3b-55ec-4d7c-b6ab-f64613a67749\\scratchpad\\shots';
const fs = require('fs');
fs.mkdirSync(shotDir, { recursive: true });

const stamp = Date.now();
const email = `qa.reserva.${stamp}@teste.com`;
const password = 'SenhaForte123!';

const results = [];
function log(msg) {
  console.log(msg);
  results.push(msg);
}

async function selectMonthPicker(page, containerId, monthValue, yearValue) {
  const selects = page.locator(`#${containerId} select`);
  await selects.nth(0).selectOption(String(monthValue));
  await selects.nth(1).selectOption(String(yearValue));
}

(async () => {
  const browser = await chromium.launch({ executablePath: CHROME_PATH, headless: true });
  const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
  const page = await context.newPage();
  const consoleErrors = [];
  page.on('console', (msg) => {
    if (msg.type() === 'error') consoleErrors.push(msg.text());
  });
  page.on('pageerror', (err) => consoleErrors.push('pageerror: ' + err.message));

  try {
    // 1. Register
    await page.goto(`${BASE}/cadastro`, { waitUntil: 'networkidle' });
    await page.fill('#name', 'QA Reserva');
    await page.fill('#email', email);
    await page.fill('#password', password);
    await page.fill('#password_confirmation', password);
    await page.click('button:has-text("Criar conta")');
    await page.waitForURL('**/dashboard', { timeout: 15000 });
    log('OK: registro e login realizados, redirecionou para /dashboard');

    // 2. Create categories (income + expense)
    await page.goto(`${BASE}/categorias`, { waitUntil: 'networkidle' });
    await page.fill('#category-name', 'Salário QA');
    await page.selectOption('#category-type', 'income');
    await page.click('button:has-text("Cadastrar categoria")');
    await page.waitForTimeout(600);
    await page.fill('#category-name', 'Contas QA');
    await page.selectOption('#category-type', 'expense');
    await page.click('button:has-text("Cadastrar categoria")');
    await page.waitForTimeout(600);
    log('OK: categorias criadas (Salário QA / Contas QA)');

    // 3. Reserva Mensal - create reserve for 2026-08, reserva_anterior = 10000
    await page.goto(`${BASE}/reserva-mensal`, { waitUntil: 'networkidle' });
    await selectMonthPicker(page, 'reserve-competency', 8, 2026);
    await page.fill('#previous-reserve', '10000');
    await page.click('button:has-text("Cadastrar reserva")');
    await page.waitForTimeout(1000);
    log('OK: reserva 2026-08 criada com reserva_anterior=10000');
    await page.screenshot({ path: `${shotDir}/1-reserve-created.png`, fullPage: true });

    // 4. Add transactions for 2026-08: income 5200, expense 5000 (net +200)
    await page.goto(`${BASE}/transacoes`, { waitUntil: 'networkidle' });
    // income transaction
    await page.click('button:has-text("Entrada")');
    await page.selectOption('#transaction-category-income', { label: 'Salário QA (Entrada)' });
    await page.fill('#transaction-description', 'Salario QA Agosto');
    await page.fill('#transaction-amount', '5200');
    await selectMonthPicker(page, 'transaction-form-competency', 8, 2026);
    await page.click('button:has-text("Cadastrar transação")');
    await page.waitForTimeout(800);

    // expense transaction
    await page.click('button:has-text("Saída")');
    await page.selectOption('#transaction-category-expense', { label: 'Contas QA (Saída)' });
    await page.fill('#transaction-description', 'Contas QA Agosto');
    await page.fill('#transaction-amount', '5000');
    await selectMonthPicker(page, 'transaction-form-competency', 8, 2026);
    await page.click('button:has-text("Cadastrar transação")');
    await page.waitForTimeout(800);
    log('OK: transacoes de 2026-08 lancadas (income 5200 / expense 5000)');
    await page.screenshot({ path: `${shotDir}/2-transactions.png`, fullPage: true });

    // 5. Back to Reserva Mensal - check computed values
    await page.goto(`${BASE}/reserva-mensal`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(500);
    const cardsText = await page.locator('.financial-card').allTextContents();
    log('CARDS (Ultima reserva / Ultimo investimento / Total guardado / Saldo final): ' + JSON.stringify(cardsText));
    const historyRowText = await page.locator('table.premium-table tbody tr').first().innerText();
    log('LINHA DO HISTORICO (2026-08): ' + historyRowText.replace(/\n/g, ' | '));
    await page.screenshot({ path: `${shotDir}/3-reserve-with-real-values.png`, fullPage: true });

    // 6. Edit the reserve changing only observations - regression test for the 422 bug
    await page.click('table.premium-table tbody tr >> nth=0 >> button:has-text("Editar")');
    await page.waitForTimeout(400);
    await page.fill('#observations', 'Observacao editada via QA automatizado');
    await page.click('button:has-text("Salvar alterações")');
    await page.waitForTimeout(800);
    const generalErrorAfterEdit = await page.locator('text=/422|erro/i').count();
    log(`REGRESSAO 422: erros visiveis apos salvar edicao = ${generalErrorAfterEdit} (esperado baixo/0)`);
    await page.screenshot({ path: `${shotDir}/4-after-edit.png`, fullPage: true });

    // 7. Add manual lançamento "Rendimento" 100 while editing
    await page.fill('#entry-description', 'Rendimento');
    await page.fill('#entry-amount', '100');
    await page.click('button:has-text("Adicionar lançamento")');
    await page.waitForTimeout(1000);
    const cardsAfterEntry = await page.locator('.financial-card').allTextContents();
    log('CARDS apos lancamento de Rendimento 100: ' + JSON.stringify(cardsAfterEntry));
    await page.screenshot({ path: `${shotDir}/5-after-entry.png`, fullPage: true });

    // 8. Create reserve for next competency 2026-09, check reserva_anterior auto-suggestion
    await page.click('button:has-text("Cancelar")');
    await page.waitForTimeout(300);
    await selectMonthPicker(page, 'reserve-competency', 9, 2026);
    await page.waitForTimeout(800);
    const suggestedValue = await page.inputValue('#previous-reserve');
    log(`SUGESTAO reserva_anterior para 2026-09 (esperado ~10300): ${suggestedValue}`);
    await page.screenshot({ path: `${shotDir}/6-suggested-previous.png`, fullPage: true });

    // 9. Chart check - bar heights
    const barHeights = await page.locator('.responsive-chart-frame span[style*="height"]').evaluateAll(
      (els) => els.map((el) => el.style.height)
    );
    log('ALTURAS DAS BARRAS DO GRAFICO: ' + JSON.stringify(barHeights));

    // 10. Dashboard visual check
    await page.goto(`${BASE}/dashboard`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(1200);
    await page.screenshot({ path: `${shotDir}/7-dashboard.png`, fullPage: true });
    const dashboardCards = await page.locator('.financial-card').allTextContents();
    log('DASHBOARD CARDS: ' + JSON.stringify(dashboardCards));

    log('CONSOLE ERRORS TOTAL: ' + consoleErrors.length);
    if (consoleErrors.length) {
      log('CONSOLE ERRORS: ' + JSON.stringify(consoleErrors.slice(0, 20)));
    }
  } catch (err) {
    log('EXCEPTION: ' + err.stack);
    await page.screenshot({ path: `${shotDir}/error.png`, fullPage: true }).catch(() => {});
  } finally {
    await browser.close();
  }

  fs.writeFileSync(`${shotDir}/report.txt`, results.join('\n'));
})();
