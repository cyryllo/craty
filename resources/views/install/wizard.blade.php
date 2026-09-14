<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ __('Installation') }} — Craty</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-100 min-h-screen py-10">
        @php
            // Krok, na którym ma się otworzyć kreator po nieudanej próbie
            // zapisu — czysto widokowa logika, żeby nie chować od razu w
            // kroku 1 błędu, który dotyczy pól z kroku 2 albo 4.
            $errorStep = 1;
            if ($errors->hasAny(['db_host', 'db_port', 'db_database', 'db_username', 'db_password']) || session('installError')) {
                $errorStep = 2;
            } elseif ($errors->has('app_name')) {
                $errorStep = 3;
            } elseif ($errors->hasAny(['admin_name', 'admin_email', 'admin_password'])) {
                $errorStep = 4;
            }
        @endphp

        <div class="max-w-2xl mx-auto px-4" x-data="{
            step: {{ $errorStep }},
            dbTesting: false,
            dbResult: null,
            testConnection() {
                this.dbTesting = true;
                this.dbResult = null;
                fetch('{{ route('install.test-database') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        db_host: this.$refs.db_host.value,
                        db_port: this.$refs.db_port.value,
                        db_database: this.$refs.db_database.value,
                        db_username: this.$refs.db_username.value,
                        db_password: this.$refs.db_password.value,
                    }),
                })
                    .then(r => r.json().then(body => ({ status: r.status, body })))
                    .then(({ status, body }) => {
                        this.dbTesting = false;
                        this.dbResult = status === 200
                            ? { ok: true, message: '{{ __('Connection successful.') }}' }
                            : { ok: false, message: body.message ?? '{{ __('Connection failed.') }}' };
                    })
                    .catch(() => {
                        this.dbTesting = false;
                        this.dbResult = { ok: false, message: '{{ __('Connection failed.') }}' };
                    });
            },
        }">
            <div class="flex flex-col items-center gap-2 mb-6">
                <x-application-logo class="w-14 h-14 text-gray-700" />
                <h1 class="font-semibold text-xl text-gray-800">{{ __('Craty installation') }}</h1>
            </div>

            {{-- Wskaźnik kroków --}}
            <div class="flex items-center justify-center gap-2 mb-6 text-xs font-medium text-gray-400">
                @foreach ([1 => __('Requirements'), 2 => __('Database'), 3 => __('App name'), 4 => __('Administrator'), 5 => __('Finish')] as $n => $label)
                    <button type="button" @click="step = {{ $n }}"
                            :class="step === {{ $n }} ? 'text-indigo-600' : ''"
                            class="flex items-center gap-1.5 hover:text-gray-600">
                        <span :class="step === {{ $n }} ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-500'" class="w-5 h-5 rounded-full flex items-center justify-center text-[11px]">{{ $n }}</span>
                        <span class="hidden sm:inline">{{ $label }}</span>
                    </button>
                    @if ($n < 5) <span class="text-gray-300">—</span> @endif
                @endforeach
            </div>

            @if (session('installError'))
                <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-md p-4 mb-4">
                    {{ session('installError') }}
                </div>
            @endif

            <form method="POST" action="{{ route('install.store') }}" class="bg-white rounded-lg shadow p-6">
                @csrf

                {{-- Krok 1: Wymagania --}}
                <div x-show="step === 1" x-cloak>
                    <h2 class="font-medium text-gray-900 mb-4">{{ __('Environment requirements') }}</h2>
                    <ul class="space-y-2 text-sm mb-4">
                        <li class="flex items-center justify-between border-b border-gray-100 pb-2">
                            <span>{{ $requirements['php']['label'] }}</span>
                            <span class="flex items-center gap-2">
                                <span class="text-gray-400">{{ $requirements['php']['detail'] }}</span>
                                <span class="{{ $requirements['php']['ok'] ? 'text-emerald-600' : 'text-red-600' }}">{{ $requirements['php']['ok'] ? '✓' : '✗' }}</span>
                            </span>
                        </li>
                        @foreach ($requirements['extensions'] as $ext)
                            <li class="flex items-center justify-between border-b border-gray-100 pb-2">
                                <span>{{ $ext['label'] }}</span>
                                <span class="{{ $ext['ok'] ? 'text-emerald-600' : 'text-red-600' }}">{{ $ext['ok'] ? '✓' : '✗' }}</span>
                            </li>
                        @endforeach
                        @foreach (['storage', 'bootstrap_cache', 'env'] as $key)
                            <li class="flex items-center justify-between border-b border-gray-100 pb-2">
                                <span>{{ $requirements[$key]['label'] }}</span>
                                <span class="{{ $requirements[$key]['ok'] ? 'text-emerald-600' : 'text-red-600' }}">{{ $requirements[$key]['ok'] ? '✓' : '✗' }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @php
                        $allOk = $requirements['php']['ok']
                            && collect($requirements['extensions'])->every(fn ($e) => $e['ok'])
                            && $requirements['storage']['ok'] && $requirements['bootstrap_cache']['ok'] && $requirements['env']['ok'];
                    @endphp
                    @unless ($allOk)
                        <p class="text-sm text-red-600 mb-4">{{ __('Fix the issues marked in red above before continuing.') }}</p>
                    @endunless
                    <div class="flex justify-end">
                        <button type="button" @click="step = 2" @disabled(! $allOk) class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed">{{ __('Next') }}</button>
                    </div>
                </div>

                {{-- Krok 2: Baza danych --}}
                <div x-show="step === 2" x-cloak>
                    <h2 class="font-medium text-gray-900 mb-4">{{ __('Database') }}</h2>
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div class="col-span-2">
                            <x-input-label for="db_host" :value="__('Host')" />
                            <x-text-input id="db_host" x-ref="db_host" name="db_host" class="mt-1 block w-full" value="{{ old('db_host', $defaults['db_host']) }}" />
                            <x-input-error :messages="$errors->get('db_host')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="db_port" :value="__('Port')" />
                            <x-text-input id="db_port" x-ref="db_port" name="db_port" value="{{ old('db_port', $defaults['db_port']) }}" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('db_port')" class="mt-1" />
                        </div>
                    </div>
                    <div class="mb-4">
                        <x-input-label for="db_database" :value="__('Database name')" />
                        <x-text-input id="db_database" x-ref="db_database" name="db_database" class="mt-1 block w-full" value="{{ old('db_database') }}" />
                        <x-input-error :messages="$errors->get('db_database')" class="mt-1" />
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <x-input-label for="db_username" :value="__('Username')" />
                            <x-text-input id="db_username" x-ref="db_username" name="db_username" class="mt-1 block w-full" value="{{ old('db_username') }}" autocomplete="off" />
                            <x-input-error :messages="$errors->get('db_username')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="db_password" :value="__('Password')" />
                            <x-text-input id="db_password" x-ref="db_password" name="db_password" type="password" class="mt-1 block w-full" autocomplete="off" />
                            <x-input-error :messages="$errors->get('db_password')" class="mt-1" />
                        </div>
                    </div>

                    <div class="mb-4">
                        <x-secondary-button type="button" @click="testConnection()" x-bind:disabled="dbTesting">
                            <span x-show="!dbTesting">{{ __('Test connection') }}</span>
                            <span x-show="dbTesting" x-cloak>{{ __('Testing…') }}</span>
                        </x-secondary-button>
                        <p class="mt-2 text-sm" x-show="dbResult" x-cloak
                           :class="dbResult && dbResult.ok ? 'text-emerald-600' : 'text-red-600'"
                           x-text="dbResult ? dbResult.message : ''"></p>
                    </div>

                    <div class="flex justify-between">
                        <button type="button" @click="step = 1" class="text-sm text-gray-500 hover:underline">{{ __('Back') }}</button>
                        <button type="button" @click="step = 3" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">{{ __('Next') }}</button>
                    </div>
                </div>

                {{-- Krok 3: Nazwa aplikacji --}}
                <div x-show="step === 3" x-cloak>
                    <h2 class="font-medium text-gray-900 mb-4">{{ __('App name') }}</h2>
                    <div class="mb-4">
                        <x-input-label for="app_name" :value="__('App name')" />
                        <x-text-input id="app_name" name="app_name" class="mt-1 block w-full" value="{{ old('app_name') }}" placeholder="Craty" />
                        <p class="mt-1 text-xs text-gray-500">{{ __('You can change this and the logo later from Settings → App settings.') }}</p>
                        <x-input-error :messages="$errors->get('app_name')" class="mt-1" />
                    </div>
                    <div class="flex justify-between">
                        <button type="button" @click="step = 2" class="text-sm text-gray-500 hover:underline">{{ __('Back') }}</button>
                        <button type="button" @click="step = 4" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">{{ __('Next') }}</button>
                    </div>
                </div>

                {{-- Krok 4: Konto administratora --}}
                <div x-show="step === 4" x-cloak>
                    <h2 class="font-medium text-gray-900 mb-4">{{ __('Administrator account') }}</h2>
                    <div class="mb-4">
                        <x-input-label for="admin_name" :value="__('Name')" />
                        <x-text-input id="admin_name" name="admin_name" class="mt-1 block w-full" value="{{ old('admin_name') }}" />
                        <x-input-error :messages="$errors->get('admin_name')" class="mt-1" />
                    </div>
                    <div class="mb-4">
                        <x-input-label for="admin_email" :value="__('Email')" />
                        <x-text-input id="admin_email" name="admin_email" type="email" class="mt-1 block w-full" value="{{ old('admin_email') }}" autocomplete="off" />
                        <x-input-error :messages="$errors->get('admin_email')" class="mt-1" />
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <x-input-label for="admin_password" :value="__('Password')" />
                            <x-text-input id="admin_password" name="admin_password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('admin_password')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="admin_password_confirmation" :value="__('Confirm password')" />
                            <x-text-input id="admin_password_confirmation" name="admin_password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mb-4">{{ __('This account cannot be deleted or demoted later — it is your permanent way back into the app.') }}</p>
                    <div class="flex justify-between">
                        <button type="button" @click="step = 3" class="text-sm text-gray-500 hover:underline">{{ __('Back') }}</button>
                        <button type="button" @click="step = 5" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">{{ __('Next') }}</button>
                    </div>
                </div>

                {{-- Krok 5: Dane przykładowe + podsumowanie --}}
                <div x-show="step === 5" x-cloak>
                    <h2 class="font-medium text-gray-900 mb-4">{{ __('Finish') }}</h2>
                    <label class="flex items-start gap-2 mb-6">
                        <input type="checkbox" name="demo_data" value="1" @checked(old('demo_data')) class="mt-1 rounded border-gray-300">
                        <span class="text-sm text-gray-700">
                            {{ __('Load sample data') }}
                            <span class="block text-xs text-gray-500">{{ __('A few example categories, a warehouse and items, so the app has something to show right away.') }}</span>
                        </span>
                    </label>
                    <p class="text-sm text-gray-500 mb-4">{{ __('Clicking "Install" will write your database credentials to .env, run migrations, and create the administrator account.') }}</p>
                    <div class="flex justify-between">
                        <button type="button" @click="step = 4" class="text-sm text-gray-500 hover:underline">{{ __('Back') }}</button>
                        <x-primary-button>{{ __('Install') }}</x-primary-button>
                    </div>
                </div>
            </form>
        </div>
    </body>
</html>
