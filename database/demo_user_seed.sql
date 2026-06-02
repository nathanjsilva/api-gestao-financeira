-- Demo data para API de Gestão Financeira
-- Use este script no MySQL Workbench para inserir um usuário demo
-- e dados fictícios sem apagar registros existentes.

INSERT IGNORE INTO users (name, email, password, email_verified_at, remember_token, created_at, updated_at)
VALUES
('Usuário Demo', 'demo@gestaofinanceira.com', '$2y$10$fBvAngO.aLvvGWCPxHujHeh435ySM0Opg9sRSpBUusw65me95e0BK', NOW(), NULL, NOW(), NOW());

SET @user_id = (SELECT id FROM users WHERE email = 'demo@gestaofinanceira.com');

INSERT IGNORE INTO categories (user_id, name, type, created_at, updated_at)
VALUES
(@user_id, 'Salário', 'income', NOW(), NOW()),
(@user_id, 'Freelance', 'income', NOW(), NOW()),
(@user_id, 'Alimentação', 'expense', NOW(), NOW()),
(@user_id, 'Transporte', 'expense', NOW(), NOW()),
(@user_id, 'Lazer', 'expense', NOW(), NOW()),
(@user_id, 'Contas', 'expense', NOW(), NOW());

SET @salary_category = (SELECT id FROM categories WHERE user_id = @user_id AND name = 'Salário');
SET @freelance_category = (SELECT id FROM categories WHERE user_id = @user_id AND name = 'Freelance');
SET @alimentacao_category = (SELECT id FROM categories WHERE user_id = @user_id AND name = 'Alimentação');
SET @transporte_category = (SELECT id FROM categories WHERE user_id = @user_id AND name = 'Transporte');
SET @lazer_category = (SELECT id FROM categories WHERE user_id = @user_id AND name = 'Lazer');
SET @contas_category = (SELECT id FROM categories WHERE user_id = @user_id AND name = 'Contas');

INSERT INTO transactions (user_id, category_id, description, amount, type, status, competency, is_recurring, created_at, updated_at)
VALUES
(@user_id, @salary_category, 'Salário do mês', 7500.00, 'income', 'paid', '2026-05', false, NOW(), NOW()),
(@user_id, @freelance_category, 'Projeto freelance', 1200.00, 'income', 'paid', '2026-05', false, NOW(), NOW()),
(@user_id, @alimentacao_category, 'Supermercado', 540.30, 'expense', 'paid', '2026-05', false, NOW(), NOW()),
(@user_id, @transporte_category, 'Combustível', 185.20, 'expense', 'paid', '2026-05', false, NOW(), NOW()),
(@user_id, @lazer_category, 'Streaming e cinema', 120.00, 'expense', 'paid', '2026-05', false, NOW(), NOW()),
(@user_id, @contas_category, 'Conta de luz', 220.50, 'expense', 'paid', '2026-05', false, NOW(), NOW()),
(@user_id, @contas_category, 'Conta de internet', 150.00, 'expense', 'pending', '2026-06', false, NOW(), NOW()),
(@user_id, @alimentacao_category, 'Almoço de trabalho', 52.90, 'expense', 'pending', '2026-06', false, NOW(), NOW()),
(@user_id, @salary_category, 'Salário antecipado', 7600.00, 'income', 'pending', '2026-06', false, NOW(), NOW());

INSERT IGNORE INTO monthly_reserves (user_id, competency, reserva_anterior, investimento, observations, created_at, updated_at)
VALUES
(@user_id, '2026-05', 1200.00, 800.00, 'Reserva inicial para Maio', NOW(), NOW()),
(@user_id, '2026-06', 950.00, 900.00, 'Planejamento de investimentos para Junho', NOW(), NOW());

SELECT 'Usuário demo criado ou existente: demo@gestaofinanceira.com | senha: password123' AS mensagem;
