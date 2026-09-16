<?php

namespace App\Http\Controllers;

use App\Exceptions\UpdatePackageException;
use App\Services\UpdateService;
use App\Support\PhpUploadLimits;
use Illuminate\Http\Request;

/**
 * Ustawienia → Aktualizacje. Jedna z najbardziej uprzywilejowanych akcji w
 * całej appce (nadpisuje kod PHP, który się potem wykonuje) — patrz
 * `password.confirm` na trasie upload w routes/web.php i TODO.md "Moduł
 * Aktualizacje" po pełne uzasadnienie decyzji.
 */
class UpdateController extends Controller
{
    public function index(UpdateService $updates)
    {
        return view('settings.updates', [
            'currentVersion' => $updates->currentVersion(),
            'maxUploadBytes' => PhpUploadLimits::maxUploadBytes(),
            'uploadLimitSufficient' => PhpUploadLimits::meetsRecommendedMinimum(),
        ]);
    }

    public function upload(Request $request, UpdateService $updates)
    {
        // PHP samo czyści $_POST/$_FILES (bez wyjątku) gdy body przekroczy
        // post_max_size — bez tej detekcji admin dostałby tylko mylącą
        // walidację "pole jest wymagane" zamiast prawdziwej przyczyny.
        // Patrz PhpUploadLimits i TODO.md "Drobne rzeczy zauważone przy budowie".
        if (PhpUploadLimits::requestWasTruncated($request)) {
            return back()->with('updateError', __('The upload was rejected before it reached the application — the package is most likely larger than this server currently allows (:limit). Ask your hosting provider to raise upload_max_filesize/post_max_size in php.ini, or check the limit shown on this page.', [
                'limit' => number_format(PhpUploadLimits::maxUploadBytes() / 1048576, 1).' MB',
            ]));
        }

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
}
