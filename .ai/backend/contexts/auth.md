# context: auth — Autenticação

## Responsabilidade

Registro, login e logout de usuários usando Laravel Sanctum (Bearer tokens).

---

## Arquivos Envolvidos

| Tipo | Arquivo |
|------|---------|
| Controller | `app/Http/Controllers/Api/AuthController.php` |
| Service | `app/Services/AuthService.php` |
| Repository | `app/Repositories/UserRepository.php` |
| Request (registro) | `app/Http/Requests/Auth/RegisterRequest.php` |
| Request (login) | `app/Http/Requests/Auth/LoginRequest.php` |
| Middleware | `app/Http/Middleware/ForceJsonResponse.php` |
| Model | `app/Models/User.php` |
| Migration | `database/migrations/0001_01_01_000000_create_users_table.php` |

---

## Fluxo de Registro

```
POST /api/auth/register
    → RegisterRequest (valida: name, email único, password confirmado min:8)
    → AuthController::register()
    → AuthService::cadastrar()
        → UserRepository::buscarPorEmail() — verifica duplicata
        → UserRepository::criar() — cria usuário com password hashed
        → AuthService::gerarToken() — cria Sanctum token
    → Retorna: { user: {...}, token: "plaintext_token" }
```

**Throttle**: `throttle:register`

---

## Fluxo de Login

```
POST /api/auth/login
    → LoginRequest (valida: email, password)
    → AuthController::login()
    → AuthService::validarCredenciais(email, senha)
        → UserRepository::buscarPorEmail()
        → Hash::check(senha, user->password)
    → AuthService::gerarToken(usuario, 'api-token')
    → Retorna: { user: {...}, token: "plaintext_token" }
```

**Throttle**: `throttle:auth`
**Erro**: 401 se credenciais inválidas

---

## Fluxo de Logout

```
POST /api/auth/logout  [auth:sanctum]
    → AuthController::logout()
    → AuthService::revogarTokenAtual(usuario)
        → $usuario->currentAccessToken()->delete()
    → Retorna: { message: "Logout realizado com sucesso." }
```

---

## Regras de Negócio

- Email deve ser único na tabela `users`
- Senha mínima de 8 caracteres com confirmação
- Token gerado pelo Sanctum (`createToken()`) — retorna `plainTextToken`
- Cada logout revoga apenas o token atual (outros devices permanecem logados)
- `user_id` em todas as demais rotas vem do token, nunca do request body

---

## Validações (RegisterRequest)

```php
'name'                  => ['required', 'string', 'max:255'],
'email'                 => ['required', 'email', 'unique:users,email'],
'password'              => ['required', 'string', 'min:8', 'confirmed'],
'password_confirmation' => ['required'],
```

---

## Resposta de Sucesso

```json
{
  "user": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@example.com"
  },
  "token": "1|abc123..."
}
```

---

## Pontos de Atenção

- Não armazene tokens no backend além do que o Sanctum já faz
- O frontend armazena o token no `localStorage` via `authStore`
- O Axios adiciona automaticamente `Authorization: Bearer {token}`
- Em 401, o interceptor do Axios redireciona para `/login`
