<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class PurchaseOrderController extends Controller
{
    private const ACCESS_MODULE = 'gmisl.procurement.purchase_order';

    /**
     * Display Purchase Order index page
     */
    public function index()
    {
        return Inertia::render('Procurement/PurchaseOrder/Index', [
            'title' => 'Purchase Requisition',
            'description' => 'Manage Purchase Requisitions',
        ]);
    }
}
