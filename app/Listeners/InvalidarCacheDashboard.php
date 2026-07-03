<?php

namespace App\Listeners;

use App\Events\DadosFinanceirosAlterados;
use Illuminate\Support\Facades\Cache;

class InvalidarCacheDashboard
{
    public function handle(DadosFinanceirosAlterados $event): void
    {
        Cache::increment("dashboard:user:{$event->usuarioId}:version");
    }
}
