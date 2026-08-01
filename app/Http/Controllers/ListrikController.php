<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Pagination\LengthAwarePaginator;

class ListrikController extends Controller
{
    public function index(Request $request)
    {
        // For now provide sample data. Replace with DB queries as needed.
        $sample = [];
        for ($i = 1; $i <= 25; $i++) {
            $sample[] = [
                'id' => $i,
                'tanggal' => date('Y-m-d', strtotime("-" . ($i - 1) . " days")),
                'jam' => '17:00',
                'lbp' => 125000 + $i * 100,
                'wbp' => 660000 + $i * 10,
                'total' => 785000 + $i * 110,
                'kvarh' => round(0.000 + $i * 0.001, 3),
                'tt' => null,
            ];
        }

        $page = max(1, (int) $request->get('page', 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $itemsForCurrentPage = array_slice($sample, $offset, $perPage);

        $paginator = new LengthAwarePaginator(
            $itemsForCurrentPage,
            count($sample),
            $perPage,
            $page,
            ['path' => url('gmium/listrik')]
        );

        return Inertia::render('GMIUM/Listrik/Index', [
            'records' => $paginator,
            'filters' => ['search' => $request->get('search')],
        ]);
    }
}
