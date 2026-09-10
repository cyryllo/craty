<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /** Osobista preferencja języka — ustawiana w Profilu, dostępna dla każdej roli. */
    public function update(Request $request)
    {
        $data = $request->validate([
            'locale' => ['nullable', Rule::in(array_keys(AppSetting::LOCALES))],
        ]);

        $request->user()->update(['locale' => $data['locale'] ?? null]);

        return back()->with('status', 'locale-updated');
    }
}
