<?php

namespace App\Http\Controllers;

use App\Exceptions\BackupException;
use App\Models\ActivityLog;
use App\Models\AppSetting;
use App\Services\BackupService;
use App\Support\PhpUploadLimits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Ustawienia → Kopie zapasowe (tylko administrator). Pobieranie, wgrywanie i
 * przywracanie wymagają świeżo potwierdzonego hasła (password.confirm na
 * trasach), a przyciski tych akcji pojawiają się dopiero po potwierdzeniu,
 * z tego samego powodu co przy Aktualizacjach: przeglądarka nie powtórzy
 * wysłania pliku po przekierowaniu na stronę potwierdzenia hasła.
 */
class BackupController extends Controller
{
    public function index(Request $request, BackupService $backups)
    {
        return view('settings.backups', [
            'backups' => $backups->list(),
            'setting' => AppSetting::current(),
            'maxUploadBytes' => PhpUploadLimits::maxUploadBytes(),
            'passwordConfirmed' => (time() - $request->session()->get('auth.password_confirmed_at', 0))
                <= config('auth.password_timeout', 10800),
        ]);
    }

    /** Cel przekierowania password.confirm, jak settings.updates.confirm. */
    public function confirmed()
    {
        return redirect()->route('settings.backups.index');
    }

    public function store(BackupService $backups)
    {
        try {
            $name = $backups->create();
        } catch (BackupException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('Backup created: :name', ['name' => $name]));
    }

    public function settings(Request $request)
    {
        $data = $request->validate([
            'backup_schedule' => ['required', Rule::in(array_keys(BackupService::SCHEDULES))],
            'backup_retention_days' => ['required', 'integer', Rule::in(array_keys(BackupService::RETENTION_OPTIONS))],
        ]);
        AppSetting::current()->fill($data)->save();

        return back()->with('status', __('Backup settings saved.'));
    }

    public function download(string $name, BackupService $backups)
    {
        try {
            $path = $backups->path($name);
        } catch (BackupException) {
            abort(404);
        }
        ActivityLog::record('backup.downloaded', null, ['name' => $name]);

        return response()->download($path, $name, ['Content-Type' => 'application/zip']);
    }

    public function destroy(string $name, BackupService $backups)
    {
        try {
            $backups->delete($name);
        } catch (BackupException) {
            abort(404);
        }

        return back()->with('status', __('Backup deleted.'));
    }

    public function upload(Request $request, BackupService $backups)
    {
        $request->validate(['backup' => ['required', 'file', 'mimes:zip']]);

        try {
            $name = $backups->import($request->file('backup'));
        } catch (BackupException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('Backup uploaded: :name. You can restore it from the list.', ['name' => $name]));
    }

    public function restore(Request $request, string $name, BackupService $backups)
    {
        try {
            $safety = $backups->restore($name, $request->user()->name);
        } catch (BackupException $e) {
            return back()->with('error', $e->getMessage());
        }

        // Konta pochodzą teraz z kopii, więc bieżąca sesja może wskazywać kogoś
        // innego albo nikogo: wylogowanie i ponowne logowanie danymi z kopii.
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', __('Backup restored. Sign in again with an account from the backup. The state from before restoring was saved as :name.', ['name' => $safety]));
    }
}
