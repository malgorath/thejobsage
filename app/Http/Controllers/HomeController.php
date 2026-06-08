<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Serves the public landing page.
 */
class HomeController extends Controller
{
    /**
     * Render the marketing / landing page.
     */
    public function index(): View
    {
        return view('index');
    }
}
