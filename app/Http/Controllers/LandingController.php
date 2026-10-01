<?php
namespace App\Http\Controllers;

use App\Models\CardDuelma;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function home(Request $request)
    {
        $query = CardDuelma::query();

        // Server-side filtering
        if ($request->filled('set')) {
            $query->where('set_code', 'like', '%' . $request->set . '%');
        }
        if ($request->filled('number')) {
            $query->where('collector_number', 'like', '%' . $request->number . '%');
        }
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // Paginate results (e.g. 24 cards per page) and keep search params in the URL
        $cards = $query->latest()->paginate(24)->withQueryString();

        return view('landing.home', compact('cards'));
    }
}
