<?php

namespace App\Events;

class DadosFinanceirosAlterados
{
    public function __construct(
        public readonly int $usuarioId,
    ) {}
}
