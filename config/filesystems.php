<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        // Kopie zapasowe (moduł Backup) — celowo osobny dysk, nigdy publiczny:
        // zawierają pełny dump bazy danych, nie mogą wylądować pod URL-em
        // tak jak dysk "public".
        'backups' => [
            'driver' => 'local',
            'root' => storage_path('app/backups'),
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            // Celowo BEZ 'url' => env('APP_URL').'/storage' — to sprawiało, że
            // Storage::disk('public')->url() zawsze zwracał zdjęcia/QR pod
            // stałym hostem z .env, więc wchodząc na appkę z telefonu po
            // adresie IP w sieci lokalnej (inny host niż APP_URL) obrazki
            // 404-owały (link i tak wskazywał na "localhost"). Bez tego
            // klucza Laravel (FilesystemAdapter::getLocalUrl()) zwraca ścieżkę
            // względem korzenia ("/storage/..."), którą przeglądarka sama
            // dopełnia aktualnym hostem — działa jednocześnie na localhost,
            // adresie LAN i prawdziwej domenie produkcyjnej, bez konfiguracji.
            // Tam, gdzie faktycznie potrzeba pełnego URL-a poza kontekstem
            // strony (eksport CSV do OLX), owijamy tę względną ścieżkę
            // helperem url() w miejscu użycia — patrz SaleListingController.
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
