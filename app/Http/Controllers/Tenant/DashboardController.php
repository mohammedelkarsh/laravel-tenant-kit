<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('tenant.dashboard', [
            'tenantName' => tenant('name'),
            'tenantId' => tenant('id'),
            'memberCount' => User::count(),
            'billingUrl' => 'http://'.config('app.central_domain').'/billing/'.tenant('id'),
        ]);
    }
}
