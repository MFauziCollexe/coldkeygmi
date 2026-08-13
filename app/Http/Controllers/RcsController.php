<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class RcsController extends Controller
{
    public function index()
    {
        return Inertia::render('GMISL/Utility/Rcs/Index');
    }
}
