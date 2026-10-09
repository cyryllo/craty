<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;

/**
 * Ustawienia → Opcje sprzedaży (tylko admin, tylko przy włączonym module
 * Sprzedaż): wszystko o publicznej stronie głównej w jednym miejscu — opis
 * sklepu, dane kontaktowe, polityka prywatności i regulamin.
 */
class ShopSettingController extends Controller
{
    public function edit()
    {
        return view('settings.shop', ['setting' => AppSetting::current()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'shop_description' => ['nullable', 'string', 'max:5000'],
            'public_contact_email' => ['nullable', 'email', 'max:255'],
            'public_contact_phone' => ['nullable', 'string', 'max:30'],
            'privacy_policy' => ['nullable', 'string', 'max:100000'],
            'terms' => ['nullable', 'string', 'max:100000'],
        ]);

        $setting = AppSetting::current();
        foreach ($data as $field => $value) {
            $setting->{$field} = filled($value) ? $value : null;
        }
        $setting->save();

        return back()->with('status', __('Sale options saved.'));
    }
}
