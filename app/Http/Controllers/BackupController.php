<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function index(BackupService $backups)
    {
        return view('settings.backup', [
            'setting' => AppSetting::current(),
            'backups' => $backups->list(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'backup_retention_days' => ['required', 'integer', 'min:1', 'max:365'],
            'backup_include_env' => ['nullable', 'boolean'],
        ]);
        $data['backup_include_env'] = $request->boolean('backup_include_env');

        AppSetting::current()->fill($data)->save();

        return redirect()->route('settings.backup.index')->with('status', __('Backup settings saved.'));
    }

    /**
     * Tworzy backup od razu (synchronicznie) — dla niedużej appki
     * wystarczające, patrz TODO. Sprawdza kod wyjścia i pokazuje PRAWDZIWY
     * błąd zamiast fałszywego "Backup created." — wcześniej ten widok w
     * ogóle nie sprawdzał, czy backup() się w ogóle udał (realnie
     * zgłoszone: admin klikał "Utwórz kopię teraz" jako diagnostykę awarii
     * modułu Aktualizacje i dostawał fałszywe potwierdzenie sukcesu).
     */
    public function run(BackupService $backups)
    {
        if ($backups->run() !== 0) {
            return back()->with('error', __('Backup failed: :reason', ['reason' => $backups->lastOutput() ?: __('unknown reason — nothing in the command output.')]));
        }

        return redirect()->route('settings.backup.index')->with('status', __('Backup created.'));
    }

    public function download(string $filename, BackupService $backups): BinaryFileResponse
    {
        abort_unless($backups->exists($filename), 404);

        return response()->download($backups->fullPath($filename));
    }

    public function destroy(string $filename, BackupService $backups)
    {
        abort_unless($backups->exists($filename), 404);

        $backups->delete($filename);

        return back()->with('status', __('Backup deleted.'));
    }
}
