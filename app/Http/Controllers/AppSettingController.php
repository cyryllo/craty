<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Services\AppIconFromLogo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AppSettingController extends Controller
{
    public function edit()
    {
        return view('settings.app', ['setting' => AppSetting::current()]);
    }

    public function update(Request $request, AppIconFromLogo $icons)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'logo_as_app_icon' => ['nullable', 'boolean'],
            // .ico nie przechodzi reguły "image", więc jawna lista; mały plik, to ikonka karty
            // przeglądarki. Bez .svg: SVG może zawierać skrypt, a leży w publicznym /storage/.
            'favicon' => ['nullable', 'file', 'mimes:png,ico,jpg,jpeg,webp,gif', 'max:512'],
            'remove_favicon' => ['nullable', 'boolean'],
            'locale' => ['nullable', 'string', Rule::in(array_keys(AppSetting::LOCALES))],
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
        $setting->logo_as_app_icon = $request->boolean('logo_as_app_icon');
        $setting->save();

        // Ikona aplikacji z logo: tworzona od razu przy zapisie (i usuwana, gdy
        // opcja albo logo znikną), żeby manifest zawsze wskazywał gotowy plik.
        if (! $icons->sync($setting)) {
            return back()->with('status', __('App settings saved.'))
                ->withErrors(['logo_as_app_icon' => __('The logo could not be turned into an app icon. Try a PNG or JPG file.')]);
        }

        return back()->with('status', __('App settings saved.'));
    }
}
