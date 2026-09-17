<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Support\Modules;
use Illuminate\Http\Request;

class ModuleSettingController extends Controller
{
    public function edit()
    {
        return view('settings.modules', ['setting' => AppSetting::current()]);
    }

    public function update(Request $request)
    {
        $setting = AppSetting::current();

        foreach (array_keys(Modules::MODULES) as $key) {
            $setting->{"module_{$key}_enabled"} = $request->boolean("module_{$key}_enabled");
        }

        $setting->save();

        return back()->with('status', __('Module settings saved.'));
    }
}
