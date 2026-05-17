<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function __construct(
        protected User $model,
    ) {}

    public function buscarPorEmail(string $email): ?User
    {
        return $this->model
            ->query()
            ->where('email', $email)
            ->first();
    }

    public function criar(array $data): User
    {
        return $this->model->query()->create($data);
    }
}
