<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ItemAttachment extends Model
{
    protected $fillable = ['item_id', 'path', 'label'];

    /**
     * Typy plików dozwolone jako załącznik (faktury, instrukcje, gwarancje,
     * zdjęcia). Celowo bez .php, .html, .svg, .js itp. — załączniki leżą w
     * publicznym /storage/, więc taki plik mógłby zostać uruchomiony na
     * serwerze albo wykonać skrypt w przeglądarce w domenie aplikacji.
     */
    public const ALLOWED_EXTENSIONS = [
        'pdf', 'txt', 'csv',
        'doc', 'docx', 'xls', 'xlsx', 'odt', 'ods', 'rtf',
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'heic',
        'zip',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
