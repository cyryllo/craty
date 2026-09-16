<?php

namespace App\Http\Controllers;

class SettingsController extends Controller
{
    /** Rozdzielnik do wszystkiego, co administracyjne — widoczność kart zależy od roli. */
    public function index()
    {
        return view('settings.index');
    }
}
