<?php

namespace App\Http\Middleware;

use App\Tenancy\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentTenant
{
    public function __construct(private CurrentTenant $current) {}

    /**
     * Signed-in users act for their own business. Public pages (M3) set it from the URL slug.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            $this->current->set($user->tenant);
        }

        return $next($request);
    }
}
