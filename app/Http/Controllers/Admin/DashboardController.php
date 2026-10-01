<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        // Add any logic here later (e.g., stats, recent orders)
        return view('admin.dashboard');
    }
}
