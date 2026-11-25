<?php

namespace App\Models;

use App\Support\Modules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Wpis dziennika aktywności. Zapisywany wyłącznie przez record(), nigdy
 * edytowany. Bezpieczeństwo i administracja zawsze, zmiany danych tylko przy
 * włączonym AppSetting::activity_log_all_enabled (decyzja właściciela).
 */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    public const RETENTION_DAYS = 365;

    protected $fillable = ['user_id', 'user_name', 'event', 'category', 'subject_type', 'subject_id', 'subject_label', 'properties', 'ip'];

    protected $casts = ['properties' => 'array'];

    // Wartości to teksty źródłowe do __() (klucze angielskie).
    public const EVENTS = [
        'auth.login' => 'Signed in',
        'auth.failed' => 'Failed sign-in attempt',
        'auth.lockout' => 'Sign-in blocked after too many attempts',
        'auth.logout' => 'Signed out',
        'auth.password_reset' => 'Password reset by email link',
        'user.created' => 'Account created',
        'user.updated' => 'Account changed',
        'user.deleted' => 'Account deleted',
        'settings.updated' => 'Settings changed',
        'update.applied' => 'Update installed',
        'update.failed' => 'Update failed',
        'data.created' => 'Added',
        'data.updated' => 'Changed',
        'data.deleted' => 'Deleted',
    ];

    /** Rodzaje rzeczy w zdarzeniach "data.*" (klasa modelu → tekst źródłowy do __()). */
    public const SUBJECTS = [
        Item::class => 'Item',
        Category::class => 'Category',
        Warehouse::class => 'Warehouse',
        Room::class => 'Room',
        StorageLocation::class => 'Location',
        Contact::class => 'Contact',
        Loan::class => 'Loan',
        LoanGroup::class => 'Kit',
        Reservation::class => 'Reservation',
        Repair::class => 'Repair',
        Inspection::class => 'Inspection',
        Consumable::class => 'Consumable',
        Stocktake::class => 'Stocktake',
        SaleListing::class => 'Sale listing',
        MachineBooking::class => 'Machine booking entry',
    ];

    /** Pola, których wartości nigdy nie trafiają do dziennika (tylko informacja, że się zmieniły). */
    public const SECRET_FIELDS = ['password', 'mail_password', 'remember_token'];

    /** Długie teksty: w dzienniku tylko "zmieniono", bez treści. */
    public const LONG_FIELDS = ['shop_description', 'privacy_policy', 'terms', 'description', 'notes', 'fault_description', 'work_done', 'return_notes'];

    /** Pola pomijane przy zmianach (techniczne). */
    public const IGNORED_FIELDS = ['created_at', 'updated_at', 'remember_token', 'email_verified_at', 'qr_path'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Zapis zdarzenia. Domyślnie wykonawca to zalogowany użytkownik, a IP z
     * bieżącego żądania. Raz na jakiś czas przy okazji usuwa stare wpisy,
     * na wypadek gdyby hosting nie uruchamiał harmonogramu zadań (cron).
     */
    public static function record(string $event, ?Model $subject = null, array $properties = [], ?User $actor = null, ?string $actorName = null): ?self
    {
        try {
            $actor ??= auth()->user();
            $request = app()->bound('request') ? request() : null;

            $entry = static::create([
                'user_id' => $actor?->id,
                'user_name' => $actorName ?? $actor?->name,
                'event' => $event,
                'category' => str_starts_with($event, 'data.') ? 'data' : 'security',
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'subject_label' => $subject ? Str::limit(static::labelFor($subject), 250) : null,
                'properties' => $properties ?: null,
                'ip' => $request?->ip(),
            ]);

            if (random_int(1, 100) === 1) {
                static::prune();
            }

            return $entry;
        } catch (\Throwable $e) {
            // Dziennik nigdy nie może zablokować właściwej akcji (logowania, zapisu przedmiotu).
            report($e);

            return null;
        }
    }

    public static function prune(): int
    {
        return static::where('created_at', '<', now()->subDays(self::RETENTION_DAYS))->delete();
    }

    /** Zmienione pola modelu jako [pole => [stara, nowa]], z ukrytymi sekretami i skróconymi długimi tekstami. */
    public static function changesOf(Model $model): array
    {
        $changes = [];
        foreach ($model->getChanges() as $field => $new) {
            if (in_array($field, self::IGNORED_FIELDS, true)) {
                continue;
            }
            $old = $model->getOriginal($field);
            $changes[$field] = in_array($field, [...self::SECRET_FIELDS, ...self::LONG_FIELDS], true)
                ? null // tylko informacja, że się zmieniło
                : [self::scalar($old), self::scalar($new)];
        }

        return $changes;
    }

    private static function scalar(mixed $value): mixed
    {
        return match (true) {
            $value instanceof \DateTimeInterface => $value->format('Y-m-d H:i'),
            is_bool($value), is_int($value), is_float($value), $value === null => $value,
            default => Str::limit((string) $value, 80),
        };
    }

    public static function labelFor(Model $model): string
    {
        return match (true) {
            $model instanceof Loan => ($model->item?->name ?? '#'.$model->item_id).' → '.$model->borrower_name,
            $model instanceof User => $model->name.' ('.$model->email.')',
            default => (string) ($model->name ?? $model->title ?? $model->label ?? $model->user_name ?? $model->borrower_name ?? $model->code ?? '#'.$model->getKey()),
        };
    }

    public function eventLabel(): string
    {
        $label = __(self::EVENTS[$this->event] ?? $this->event);

        if ($this->category === 'data' && $this->subject_type) {
            $label .= ': '.mb_strtolower(__(self::SUBJECTS[$this->subject_type] ?? class_basename($this->subject_type)));
        }

        return $label;
    }

    /** Czytelny opis zmian, np. "rola: Magazynier → Administrator; hasło zmienione". */
    public function details(): string
    {
        $p = $this->properties ?? [];
        $parts = [];

        if (isset($p['from'], $p['to'])) {
            $parts[] = $p['from'].' → '.$p['to'];
        }
        if (isset($p['error'])) {
            $parts[] = $p['error'];
        }
        foreach ($p['changes'] ?? [] as $field => $change) {
            $name = self::fieldLabel($field);
            $parts[] = $change === null
                ? __(':field changed', ['field' => $name])
                : $name.': '.self::valueLabel($field, $change[0]).' → '.self::valueLabel($field, $change[1]);
        }

        return implode('; ', $parts);
    }

    public static function fieldLabel(string $field): string
    {
        if (preg_match('/^module_(\w+)_enabled$/', $field, $m) && isset(Modules::MODULES[$m[1]])) {
            return __('module').' '.__(Modules::MODULES[$m[1]]['label']);
        }

        $labels = [
            'name' => 'Name or title', 'email' => 'Email', 'role' => 'Role', 'active' => 'Active', 'password' => 'Password',
            'locale' => 'Language', 'mail_password' => 'Mail password', 'status' => 'Status', 'condition' => 'Condition',
            'value' => 'Value', 'category_id' => 'Category', 'storage_location_id' => 'Location', 'quantity' => 'Quantity',
            'protected' => 'Main administrator account', 'returned_at' => 'Returned', 'due_at' => 'Due date', 'stationary' => 'Stationary', 'parent_id' => 'Parent category',
            // Ustawienia (AppSetting): te same nazwy co na stronach ustawień.
            'activity_log_all_enabled' => 'Also record data changes', 'password_reset_enabled' => 'Self-service password reset',
            'loan_due_notifications_enabled' => 'Email a daily summary of overdue loans', 'service_reminders_enabled' => 'Weekly service reminder',
            'logo_as_app_icon' => 'Use the logo as the app icon on phones', 'logo_path' => 'Logo', 'favicon_path' => 'Favicon',
            'public_contact_email' => 'Contact email', 'public_contact_phone' => 'Contact phone', 'shop_description' => 'Sale description',
            'privacy_policy' => 'Privacy policy', 'terms' => 'Terms', 'mail_host' => 'SMTP server', 'mail_port' => 'SMTP port',
            'mail_encryption' => 'Encryption', 'mail_username' => 'SMTP username', 'mail_from_address' => 'Sender address', 'mail_from_name' => 'Sender name',
        ];

        return mb_strtolower(__($labels[$field] ?? Str::headline($field)));
    }

    private static function valueLabel(string $field, mixed $value): string
    {
        return match (true) {
            $value === null, $value === '' => __('none'),
            is_bool($value) || in_array($field, ['active', 'stationary'], true) || str_ends_with($field, '_enabled') => $value ? __('yes') : __('no'),
            $field === 'role' => __(User::ROLES[$value] ?? (string) $value),
            $field === 'status' => __(Item::STATUSES[$value] ?? (string) $value),
            $field === 'condition' => __(Item::CONDITIONS[$value] ?? (string) $value),
            default => (string) $value,
        };
    }
}
