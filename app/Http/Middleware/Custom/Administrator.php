<?php

namespace App\Http\Middleware\Custom;

use Closure;
use App\Helpers\Qs;
use Illuminate\Support\Facades\Auth;

class Administrator
{
    public function handle($request, Closure $next)
    {
        return (Auth::check() && Qs::userIsAdministrator()) ? $next($request) : redirect()->route('login');
    }
}