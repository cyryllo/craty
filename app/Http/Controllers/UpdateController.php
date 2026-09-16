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
    public function index(Request $request, UpdateService $updates)
    {
        return view('settings.updates', [
            'currentVersion' => $updates->currentVersion(),
            'maxUploadBytes' => PhpUploadLimits::maxUploadBytes(),
            'uploadLimitSufficient' => PhpUploadLimits::meetsRecommendedMinimum(),
            // Pokazujemy formularz uploadu tylko gdy hasło jest już świeżo
            // potwierdzone (ta sama logika co Illuminate\Auth\Middleware\
            // RequirePassword) — inaczej admin wybiera plik, submituje, i
            // dopiero WTEDY password.confirm na trasie upload przekierowuje
            // go na /confirm-password. Ponowne wysłanie POST-a z plikiem po
            // takim przekierowaniu jest niemożliwe (przeglądarka nie potrafi
            // "odtworzyć" multipart body pliku po redirect()->intended()) —
            // realnie zgłoszone przez użytkownika jako "po wpisaniu hasła
            // nie kontynuuje wgrywania, trzeba wybrać paczkę od nowa".
            // Rozwiązanie: jeśli hasło nie jest świeże, w ogóle nie pokazuj
            // formularza — pokaż link do settings.updates.confirm zamiast
            // niego, który przechodzi przez cały cykl potwierdzenia (GET,
            // więc redirect()->intended() bezpiecznie wraca na tę samą
            // trasę) PRZED tym, jak admin zdąży wybrać plik.
            'passwordConfirmed' => (time() - $request->session()->get('auth.password_confirmed_at', 0))
                <= config('auth.password_timeout', 10800),
        ]);
    }

    /** Cel przekierowania password.confirm dla settings.updates.confirm — patrz komentarz w index(). */
    public function confirmed()
    {
        return redirect()->route('settings.updates.index');
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
