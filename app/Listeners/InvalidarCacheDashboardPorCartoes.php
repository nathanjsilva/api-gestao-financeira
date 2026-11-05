<?php

namespace App\Listeners;

use App\Events\CartaoDadosAlterados;
use Illuminate\Support\Facades\Cache;

class InvalidarCacheDashboardPorCartoes
{
    public function handle(CartaoDadosAlterados $event): void
    {
        Cache::increment("dashboard:user:{$event->usuarioId}:version");
    }
}
