<?php

namespace App\Http\Controllers;

use App\Services\UserHomeRouteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __invoke(UserHomeRouteService $homeRoute): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        return redirect($homeRoute->resolve(Auth::user()));
    }
}
