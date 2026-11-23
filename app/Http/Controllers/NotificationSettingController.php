<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Support\Modules;
use Illuminate\Http\Request;

class NotificationSettingController extends Controller
{
    public function edit()
    {
        return view('settings.notifications', ['setting' => AppSetting::current()]);
    }

    public function update(Request $request)
    {
        $setting = AppSetting::current();
        $setting->password_reset_enabled = $request->boolean('password_reset_enabled');
        // Jak przy Serwisie niżej: pole jest tylko przy włączonym module Wypożyczenia.
        if (Modules::isEnabled('loans')) {
            $setting->loan_due_notifications_enabled = $request->boolean('loan_due_notifications_enabled');
        }
        // Pole jest na stronie tylko przy włączonym module Serwis; bez niego
        // zapis formularza nie rusza zapamiętanego ustawienia.
        if (Modules::isEnabled('service')) {
            $setting->service_reminders_enabled = $request->boolean('service_reminders_enabled');
        }
        $setting->save();

        return back()->with('status', __('Notification settings saved.'));
    }
}
