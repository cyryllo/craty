<?php

namespace App\Http\Controllers;

use App\Support\AppVersion;

class SettingsController extends Controller
{
    /** Rozdzielnik do wszystkiego, co administracyjne — widoczność kart zależy od roli. */
    public function index(AppVersion $version)
    {
        return view('settings.index', ['appVersion' => $version->current()]);
    }
}
