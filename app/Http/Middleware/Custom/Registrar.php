<?php

namespace App\Http\Middleware\Custom;

use App\Helpers\Qs;
use Closure;
use Illuminate\Support\Facades\Auth;

class Registrar
{
    public function handle($request, Closure $next)
    {
        return (Auth::check() && Qs::userIsRegistrar()) ? $next($request) : redirect()->route('login');
    }
}