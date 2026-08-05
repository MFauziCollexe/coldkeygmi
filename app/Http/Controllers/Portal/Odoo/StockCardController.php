<?php

namespace App\Http\Controllers\Portal\Odoo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockCardController extends Controller
{
    public function index(Request $request): Response
    {
        $owners = [];

        $selectedOwnerId = (int) $request->input('owner_id', 0);
        $targetProductId = $request->input('product_id');
        $startDate = $request->input('start_date', '2026-01-01');
        $endDate = $request->input('end_date', '2026-12-31');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;

        if ($targetProductId !== null && $targetProductId !== '') {
            $targetProductId = (int) $targetProductId;
        } else {
            $targetProductId = null;
        }

        try {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
        } catch (\Exception $exception) {
            $start = new \DateTime('2026-01-01');
            $end = new \DateTime('2026-01-31');
        }

        if ($end < $start) {
            $end = clone $start;
        }

        $maxEnd = (clone $start)->modify('+1 month');
        if ($end > $maxEnd) {
            $end = $maxEnd;
        }

        $startDate = $start->format('Y-m-d');
        $endDate = $end->format('Y-m-d');
        $offset = ($page - 1) * $perPage;

        $rows = [];
        $totalRows = 0;
        $customerName = null;
        $productName = null;

        return Inertia::render('Portal/Odoo/StockCard/Index', [
            'rows' => $rows,
            'owners' => $owners,
            'selectedOwnerId' => $selectedOwnerId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'targetProductId' => $targetProductId,
            'customerName' => $customerName,
            'productName' => $productName,
            'currentPage' => $page,
            'perPage' => $perPage,
            'totalRows' => $totalRows,
        ]);
    }
}
