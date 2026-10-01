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
            $num = trim($request->number);

            // If they type a specific fraction like "1/89", do an exact match!
            // This prevents "1/89" from accidentally pulling up "11/89" or "21/89"
            if (str_contains($num, '/')) {
                $query->where('collector_number', $num);
            } else {
                $query->where('collector_number', 'like', '%' . $num . '%');
            }
        }
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // Paginate results (e.g. 5 cards per page) and keep search params in the URL
        $cards = $query->latest()->paginate(5)->withQueryString();

        return view('landing.home', compact('cards'));
    }
}
