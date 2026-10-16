<?php

namespace App\Http\Middleware\Custom;

use App\Helpers\Qs;
use Closure;
use Illuminate\Support\Facades\Auth;

class Accountant
{
    public function handle($request, Closure $next)
    {
        return (Auth::check() && Qs::userIsAccountant()) ? $next($request) : redirect()->route('login');
    }
}