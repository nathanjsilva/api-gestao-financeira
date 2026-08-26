<?php

namespace App\Listeners;

use App\Events\CartaoDadosAlterados;
use Illuminate\Support\Facades\Cache;

class InvalidarCacheCartoes
{
    public function handle(CartaoDadosAlterados $event): void
    {
        Cache::increment("cards:user:{$event->usuarioId}:version");
    }
}
