<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class TenantLayout extends Component
{
    public function render(): View
    {
        return view('layouts.tenant');
    }
}
