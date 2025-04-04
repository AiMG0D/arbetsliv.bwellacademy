<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StudentSexSet
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user->isStudent() && (! isset($user->sex) || $user->sex == 'U')) {
            return redirect(url('/setsex'));
        }

        return $next($request);
    }
}
