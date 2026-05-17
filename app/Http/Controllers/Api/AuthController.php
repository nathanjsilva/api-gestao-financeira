<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $usuario = $this->authService->cadastrar($request->validated());
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Usuario cadastrado com sucesso.',
            'data' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'token' => $this->authService->gerarToken($usuario, 'auth-register'),
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $usuario = $this->authService->validarCredenciais(
            $request->validated('email'),
            $request->validated('password'),
        );

        if ($usuario === null) {
            return response()->json([
                'message' => 'Credenciais invalidas.',
            ], 401);
        }

        return response()->json([
            'message' => 'Login realizado com sucesso.',
            'data' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'token' => $this->authService->gerarToken($usuario, 'auth-login'),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var \App\Models\User $usuario */
        $usuario = $request->user();

        $this->authService->revogarTokenAtual($usuario);

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }
}
