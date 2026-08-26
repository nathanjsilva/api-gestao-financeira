<?php

namespace App\Events;

class CartaoDadosAlterados
{
    public function __construct(
        public readonly int $usuarioId,
    ) {}
}
