<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AppSettingController extends Controller
{
    public function edit()
    {
        return view('settings.app', ['setting' => AppSetting::current()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            // .ico nie przechodzi reguły "image", więc jawna lista; mały plik — to ikonka karty przeglądarki.
            'favicon' => ['nullable', 'file', 'mimes:png,ico,svg,jpg,jpeg,webp,gif', 'max:512'],
            'remove_favicon' => ['nullable', 'boolean'],
            'locale' => ['nullable', 'string', Rule::in(array_keys(AppSetting::LOCALES))],
            'public_contact_email' => ['nullable', 'email', 'max:255'],
            'public_contact_phone' => ['nullable', 'string', 'max:30'],
        ]);

        $setting = AppSetting::current();

        if ($request->hasFile('logo')) {
            if ($setting->logo_path) {
                Storage::disk('public')->delete($setting->logo_path);
            }
            $setting->logo_path = $request->file('logo')->store('branding', 'public');
        } elseif ($request->boolean('remove_logo') && $setting->logo_path) {
            Storage::disk('public')->delete($setting->logo_path);
            $setting->logo_path = null;
        }

        if ($request->hasFile('favicon')) {
            if ($setting->favicon_path) {
                Storage::disk('public')->delete($setting->favicon_path);
            }
            $setting->favicon_path = $request->file('favicon')->store('branding', 'public');
        } elseif ($request->boolean('remove_favicon') && $setting->favicon_path) {
            Storage::disk('public')->delete($setting->favicon_path);
            $setting->favicon_path = null;
        }

        $setting->name = ($data['name'] ?? null) ?: null;
        $setting->locale = ($data['locale'] ?? null) ?: null;
        // Pola kontaktu są w formularzu tylko przy włączonym module Sprzedaż —
        // przy wyłączonym nie kasujemy zapisanych wartości.
        if ($request->has('public_contact_email') || $request->has('public_contact_phone')) {
            $setting->public_contact_email = ($data['public_contact_email'] ?? null) ?: null;
            $setting->public_contact_phone = ($data['public_contact_phone'] ?? null) ?: null;
        }
        $setting->save();

        return back()->with('status', __('App settings saved.'));
    }
}
