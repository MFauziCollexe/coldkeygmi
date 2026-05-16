<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ProcurementApprovalController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Procurement/Approval/Index', [
            'title' => 'Procurement Approval',
            'description' => 'Procurement Approval',
        ]);
    }
}
