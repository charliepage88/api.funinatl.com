<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckIfAdmin
{
    private function checkIfUserIsAdmin($user)
    {
        return $user->hasRole('admin');
    }

    private function respondToUnauthorizedRequest($request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response('Unauthorized', 401);
        } else {
            return redirect()->guest(route('login'));
        }
    }

    public function handle($request, Closure $next)
    {
        if (Auth::guest()) {
            return $this->respondToUnauthorizedRequest($request);
        }

        if (! $this->checkIfUserIsAdmin(Auth::user())) {
            return $this->respondToUnauthorizedRequest($request);
        }

        return $next($request);
    }
}
