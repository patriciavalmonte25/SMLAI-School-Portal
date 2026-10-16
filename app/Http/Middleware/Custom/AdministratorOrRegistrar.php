<?php

namespace App\Http\Middleware\Custom;

use App\Helpers\Qs;
use Closure;
use Illuminate\Support\Facades\Auth;

class AdministratorOrRegistrar
{
    public function handle($request, Closure $next)
    {
        return (Auth::check() && (Qs::userIsAdministrator() || Qs::userIsRegistrar()))
            ? $next($request)
            : redirect()->route('login');
    }
}