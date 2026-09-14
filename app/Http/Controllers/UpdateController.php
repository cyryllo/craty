<?php

namespace App\Http\Controllers;

use App\Exceptions\UpdatePackageException;
use App\Services\UpdateService;
use Illuminate\Http\Request;

/**
 * Ustawienia → Aktualizacje. Jedna z najbardziej uprzywilejowanych akcji w
 * całej appce (nadpisuje kod PHP, który się potem wykonuje) — patrz
 * `password.confirm` na trasach upload/rollback w routes/web.php i
 * TODO.md "Moduł Aktualizacje" po pełne uzasadnienie decyzji.
 */
class UpdateController extends Controller
{
    public function index(UpdateService $updates)
    {
        return view('settings.updates', [
            'currentVersion' => $updates->currentVersion(),
            'canRollback' => $updates->canRollback(),
            'state' => $updates->state(),
        ]);
    }

    public function upload(Request $request, UpdateService $updates)
    {
        $request->validate([
            'package' => ['required', 'file', 'mimes:zip'],
            'checksum' => ['nullable', 'string'],
        ]);

        try {
            $path = $request->file('package')->getRealPath();
            $manifest = $updates->validatePackage($path, $request->input('checksum'));
            $updates->apply($path, $manifest);
        } catch (UpdatePackageException $e) {
            return back()->with('updateError', $e->getMessage());
        }

        return redirect()->route('settings.updates.index')
            ->with('status', __('Updated to version :version.', ['version' => $manifest['version']]));
    }

    public function rollback(UpdateService $updates)
    {
        try {
            $updates->rollback();
        } catch (UpdatePackageException $e) {
            return back()->with('updateError', $e->getMessage());
        }

        return redirect()->route('settings.updates.index')
            ->with('status', __('Rolled back to the previous code version.'));
    }
}
