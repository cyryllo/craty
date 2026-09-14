<?php

namespace App\Http\Controllers;

use App\Mail\TestMail;
use App\Models\AppSetting;
use App\Services\MailSettingsApplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class MailSettingController extends Controller
{
    public function edit()
    {
        return view('settings.mail', ['setting' => AppSetting::current()]);
    }

    public function update(Request $request)
    {
        $data = $this->validated($request);
        $setting = AppSetting::current();

        // Puste pole hasła = zostaw obecne (tak samo jak przy edycji użytkownika) —
        // formularz nie odsyła z powrotem odszyfrowanego hasła do pola input.
        if ($data['mail_password'] === null) {
            unset($data['mail_password']);
        }

        $setting->fill($data);
        $setting->save();

        return redirect()->route('settings.mail.edit')->with('status', __('Mail settings saved.'));
    }

    public function test(Request $request, MailSettingsApplier $applier)
    {
        $data = $this->validated($request, requirePassword: false);
        $request->validate(['test_email' => ['required', 'email']]);

        // Testujemy dokładnie to, co jest w formularzu teraz — nie to, co
        // ewentualnie już zapisane w bazie.
        $password = $data['mail_password'] ?? AppSetting::current()->mail_password;

        $applier->applyFromArray([
            'host' => $data['mail_host'],
            'port' => $data['mail_port'],
            'encryption' => $data['mail_encryption'],
            'username' => $data['mail_username'],
            'password' => $password,
            'from_address' => $data['mail_from_address'],
            'from_name' => $data['mail_from_name'],
        ]);

        try {
            Mail::to($request->input('test_email'))->send(new TestMail);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', __('Could not send the test email: :message', ['message' => $e->getMessage()]));
        }

        return back()->withInput()->with('status', __('Test email sent — check the inbox.'));
    }

    private function validated(Request $request, bool $requirePassword = false): array
    {
        return $request->validate([
            'mail_host' => ['required', 'string', 'max:255'],
            'mail_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'mail_encryption' => ['nullable', Rule::in(array_keys(AppSetting::MAIL_ENCRYPTIONS))],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => [$requirePassword ? 'required' : 'nullable', 'string', 'max:255'],
            'mail_from_address' => ['required', 'email', 'max:255'],
            'mail_from_name' => ['required', 'string', 'max:255'],
        ]);
    }
}
