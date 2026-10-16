<?php

namespace App\Http\Middleware\Custom;

use App\Helpers\Qs;
use Closure;
use Illuminate\Support\Facades\Auth;

class TeamSAOrRegistrar
{
    public function handle($request, Closure $next)
    {
        return (Auth::check() && (Qs::userIsTeamSA() || Qs::userIsRegistrar()))
            ? $next($request)
            : redirect()->route('login');
    }
}