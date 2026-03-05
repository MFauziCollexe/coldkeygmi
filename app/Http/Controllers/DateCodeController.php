<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Inertia\Inertia;

class DateCodeController extends Controller
{
    public function index()
    {
        $customers = Customer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('GMISL/Utility/DateCode/Index', [
            'customers' => $customers,
        ]);
    }
}

