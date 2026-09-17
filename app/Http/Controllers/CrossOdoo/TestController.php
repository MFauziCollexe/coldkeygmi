<?php

namespace App\Http\Controllers\CrossOdoo;

use App\Http\Controllers\Controller;
use App\Services\OdooXmlRpcService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TestController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('GMISL/CrossOdoo/Test/Index', [
            'products' => [],
            'templates' => [],
            'error' => null,
        ]);
    }

    public function run(Request $request)
    {
        try {
            $odoo = new OdooXmlRpcService();

            $products = $odoo->searchRead('product.product', [
                'id',
                'display_name',
                'default_code',
                'qty_available',
                'lst_price',
                'product_tmpl_id',
            ], 50);

            $templates = $odoo->searchRead('product.template', [
                'id',
                'name',
                'type',
                'categ_id',
                'list_price',
            ], 50);

            return Inertia::render('GMISL/CrossOdoo/Test/Index', [
                'products' => $products,
                'templates' => $templates,
                'error' => null,
            ]);
        } catch (\Throwable $e) {
            return Inertia::render('GMISL/CrossOdoo/Test/Index', [
                'products' => [],
                'templates' => [],
                'error' => $e->getMessage(),
            ]);
        }
    }
}
