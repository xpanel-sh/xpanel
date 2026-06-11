<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        if (!config('xpanel.home_enabled', false)) {
            return response()->view('home.disabled', [], 200);
        }

        return view('home.index');
    }
}
