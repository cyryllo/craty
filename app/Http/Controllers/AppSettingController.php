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
            'locale' => ['nullable', 'string', Rule::in(array_keys(AppSetting::LOCALES))],
            'public_marketplace_enabled' => ['nullable', 'boolean'],
            'public_contact_email' => ['required_if:public_marketplace_enabled,1', 'nullable', 'email', 'max:255'],
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

        $setting->name = ($data['name'] ?? null) ?: null;
        $setting->locale = ($data['locale'] ?? null) ?: null;
        $setting->public_marketplace_enabled = $request->boolean('public_marketplace_enabled');
        $setting->public_contact_email = ($data['public_contact_email'] ?? null) ?: null;
        $setting->public_contact_phone = ($data['public_contact_phone'] ?? null) ?: null;
        $setting->save();

        return back()->with('status', __('App settings saved.'));
    }
}
