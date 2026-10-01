<?php
namespace App\Http\Controllers;

use App\Models\CardDuelma;

class LandingController extends Controller
{
    public function home()
    {
        $cards = CardDuelma::latest()->get(); // Or paginate, or featured only
        return view('landing.home', compact('cards'));
    }
}
