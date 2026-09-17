<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
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
        $setting->loan_due_notifications_enabled = $request->boolean('loan_due_notifications_enabled');
        $setting->save();

        return back()->with('status', __('Notification settings saved.'));
    }
}
