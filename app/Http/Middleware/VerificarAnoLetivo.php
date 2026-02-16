<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AnoLetivo;

class VerificarAnoLetivo
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        if (cache()->get('ultima_verificacao_ano') !== today()->toDateString()) {
            AnoLetivo::encerrarAutomaticamente();
            cache()->put('ultima_verificacao_ano', today()->toDateString());
        }


        return $next($request);
    }
}
