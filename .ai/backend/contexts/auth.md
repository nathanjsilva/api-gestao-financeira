# Contexto: autenticação

## Responsabilidade

Registro, login e logout de usuários por tokens pessoais do Laravel Sanctum.

## Arquivos principais

- Controller: app/Http/Controllers/Api/AuthController.php
- Service: app/Services/AuthService.php
- Repository: app/Repositories/UserRepository.php
- Requests: app/Http/Requests/Auth/RegisterRequest.php e LoginRequest.php
- Model: app/Models/User.php
- Rotas: routes/api.php

## Fluxo de cadastro

~~~text
POST /api/auth/register
→ throttle:register
→ RegisterRequest aparar nome/email, converter email para minúsculas e validar
→ AuthController
→ AuthService verifica email existente e cria usuário
→ AuthService cria token pessoal Sanctum
→ JSON 201: message e data { id, name, email, token }
~~~

O request exige name, email válido e único, password com pelo menos 8 caracteres e password_confirmation correspondente.

## Fluxo de login

~~~text
POST /api/auth/login
→ throttle:auth
→ LoginRequest aparar email, converter para minúsculas e validar email/password
→ AuthController
→ AuthService localiza usuário e verifica Hash::check
→ credenciais inválidas: JSON 401
→ credenciais válidas: cria token pessoal Sanctum e retorna JSON 200
~~~

Os limitadores configurados em AppServiceProvider são 5 tentativas por minuto por combinação de email e IP no login e 3 por minuto por combinação de email e IP no cadastro. A API também tem limite geral de 60 requisições por minuto por usuário autenticado ou IP.

## Fluxo de logout

POST /api/auth/logout exige auth:sanctum. AuthService remove somente currentAccessToken. O Controller retorna JSON com message. Outros tokens do usuário continuam válidos.

## Sessão no frontend

authStore.ts mantém usuário e token e persiste a sessão em localStorage. httpClient.ts envia Authorization: Bearer quando há token. Em resposta 401, o interceptor limpa a sessão local e direciona para login. Os guards da SPA controlam navegação; a autorização dos dados é feita no backend.

## Formato de resposta

Cadastro e login retornam:

~~~json
{
  "message": "Login realizado com sucesso.",
  "data": {
    "id": 1,
    "name": "Usuário de exemplo",
    "email": "usuario@example.test",
    "token": "token-pessoal"
  }
}
~~~

O texto e o status de cadastro/login diferem conforme a operação. Tokens são exibidos apenas ao cliente na criação e são armazenados pelo Sanctum de forma protegida no banco.