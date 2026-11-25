<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Ustawienia → Dziennik aktywności (tylko administrator): lista zdarzeń z
 * filtrami i przełącznik "zapisuj też zmiany danych".
 */
class ActivityLogController extends Controller
{
    /** Zapytanie z filtrami ze strony, wspólne dla listy i eksportu CSV. */
    private function filtered(Request $request)
    {
        $category = in_array($request->query('category'), ['security', 'data'], true) ? $request->query('category') : null;
        $userId = $request->integer('user') ?: null;
        $search = trim((string) $request->query('q', ''));

        return ActivityLog::query()
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('user_name', 'like', "%{$search}%")
                ->orWhere('subject_label', 'like', "%{$search}%")
                ->orWhere('ip', 'like', "%{$search}%")))
            ->latest('created_at')->latest('id');
    }

    public function export(Request $request)
    {
        $rows = (function () use ($request) {
            foreach ($this->filtered($request)->lazy(500) as $entry) {
                yield [$entry->created_at?->format('Y-m-d H:i:s'), $entry->user_name, $entry->eventLabel(), $entry->category === 'data' ? __('data changes') : __('security and administration'), $entry->subject_label, $entry->details(), $entry->ip];
            }
        })();

        return \App\Support\Csv::download('craty-dziennik-'.now()->format('Y-m-d').'.csv', ['kiedy', 'kto', 'zdarzenie', 'rodzaj', 'czego_dotyczy', 'szczegoly', 'ip'], $rows);
    }

    public function index(Request $request)
    {
        $category = in_array($request->query('category'), ['security', 'data'], true) ? $request->query('category') : null;
        $userId = $request->integer('user') ?: null;
        $search = trim((string) $request->query('q', ''));

        $entries = $this->filtered($request)
            ->paginate(50)
            ->withQueryString();

        return view('settings.activity', [
            'entries' => $entries,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'category' => $category,
            'userId' => $userId,
            'search' => $search,
            'setting' => AppSetting::current(),
            'retentionDays' => ActivityLog::retentionDays(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'activity_log_retention_days' => ['sometimes', 'integer', Rule::in(array_keys(ActivityLog::RETENTION_OPTIONS))],
        ]);

        $setting = AppSetting::current();
        $setting->activity_log_all_enabled = $request->boolean('activity_log_all_enabled');
        if (isset($data['activity_log_retention_days'])) {
            $setting->activity_log_retention_days = (int) $data['activity_log_retention_days'];
        }
        $setting->save();

        // Krótszy okres działa od razu, a nie dopiero przy nocnym czyszczeniu.
        ActivityLog::prune();

        return back()->with('status', __('Activity log settings saved.'));
    }
}
