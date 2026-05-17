<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use DomainException;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        protected UserRepository $userRepository,
    ) {}

    public function cadastrar(array $dados): User
    {
        $usuarioExistente = $this->userRepository->buscarPorEmail($dados['email']);

        if ($usuarioExistente !== null) {
            throw new DomainException('Ja existe um usuario cadastrado com este e-mail.');
        }

        return $this->userRepository->criar($dados);
    }

    public function validarCredenciais(string $email, string $senha): ?User
    {
        $usuario = $this->userRepository->buscarPorEmail($email);

        if ($usuario === null) {
            return null;
        }

        if (! Hash::check($senha, $usuario->password)) {
            return null;
        }

        return $usuario;
    }

    public function gerarToken(User $usuario, string $nomeToken = 'api-token'): string
    {
        return $usuario->createToken($nomeToken)->plainTextToken;
    }

    public function revogarTokenAtual(User $usuario): void
    {
        $usuario->currentAccessToken()?->delete();
    }
}
