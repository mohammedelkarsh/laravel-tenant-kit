<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantNotSuspended
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Tenant|null $tenant */
        $tenant = tenant();

        if ($tenant?->isSuspended()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => __('app.tenant.suspended'),
                ], 403);
            }

            abort(503, __('app.tenant.suspended'));
        }

        return $next($request);
    }
}
