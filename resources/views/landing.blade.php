<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locales::direction() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }} — {{ __('app.landing.title') }}</title>
        <meta name="description" content="{{ __('app.landing.meta_description') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900">
        <header class="border-b border-gray-200 bg-white sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <a href="/" class="font-bold text-indigo-600">{{ config('app.name') }}</a>
                <nav class="flex items-center gap-4 text-sm">
                    <x-locale-switcher />
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-gray-900">{{ __('app.nav.dashboard') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900">{{ __('app.nav.log_in') }}</a>
                        <a href="{{ route('tenants.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">{{ __('app.nav.create_workspace') }}</a>
                    @endauth
                </nav>
            </div>
        </header>

        <main>
            <section class="bg-white border-b border-gray-200 py-20 sm:py-24 text-center">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 max-w-3xl mx-auto leading-tight">
                        {{ __('app.landing.hero_title') }} <span class="text-indigo-600">{{ __('app.landing.hero_highlight') }}</span>
                    </h1>
                    <p class="mt-6 text-lg text-gray-600 max-w-xl mx-auto">
                        {{ __('app.landing.hero_subtitle') }}
                    </p>
                    <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                        <a href="{{ route('tenants.create') }}" class="px-8 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold">{{ __('app.nav.create_workspace') }}</a>
                        <a href="http://demo.{{ config('app.central_domain') }}" class="px-8 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 font-semibold">{{ __('app.landing.live_demo') }}</a>
                    </div>
                </div>
            </section>

            <section id="architecture" class="py-16 bg-gray-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold text-center text-gray-900">{{ __('app.landing.architecture') }}</h2>
                    <div class="mt-10 grid lg:grid-cols-2 gap-6 max-w-4xl mx-auto">
                        <div class="rounded-xl border border-indigo-200 bg-white p-6">
                            <p class="text-xs font-bold uppercase text-indigo-600">{{ __('app.landing.central') }}</p>
                            <p class="mt-1 font-mono text-sm">{{ config('app.central_domain') }}</p>
                            <p class="mt-3 text-sm text-gray-600">{{ __('app.landing.central_desc') }}</p>
                        </div>
                        <div class="rounded-xl border border-emerald-200 bg-white p-6">
                            <p class="text-xs font-bold uppercase text-emerald-600">{{ __('app.landing.tenant') }}</p>
                            <p class="mt-1 font-mono text-sm">acme.{{ config('app.central_domain') }}</p>
                            <p class="mt-3 text-sm text-gray-600">{{ __('app.landing.tenant_desc') }}</p>
                        </div>
                    </div>
                    <div class="mt-6 max-w-2xl mx-auto rounded-lg bg-gray-900 px-5 py-3 font-mono text-sm text-gray-300 text-center" dir="ltr">
                        php artisan tenant:provision acme "Acme Corp" --admin=boss@acme.com
                    </div>
                </div>
            </section>

            <section id="features" class="py-16 bg-white border-y border-gray-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold text-center text-gray-900">{{ __('app.landing.included') }}</h2>
                    <div class="mt-10 grid sm:grid-cols-2 lg:grid-cols-3 gap-5 max-w-5xl mx-auto text-sm">
                        @foreach (['multi_tenancy', 'authentication', 'teams', 'billing', 'filament', 'cli'] as $feature)
                            <div class="p-5 rounded-xl border border-gray-200">
                                <strong>{{ __('app.landing.features.'.$feature.'.title') }}</strong>
                                <p class="mt-1 text-gray-600">{{ __('app.landing.features.'.$feature.'.desc') }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="py-16">
                <div class="max-w-xl mx-auto px-4 text-center">
                    <h2 class="text-2xl font-bold text-gray-900">{{ __('app.landing.try_demo') }}</h2>
                    <p class="mt-4 text-sm text-gray-600 font-mono" dir="ltr">admin@laravel-tenant-kit.test / password</p>
                    <p class="mt-1 text-sm text-gray-600 font-mono" dir="ltr">demo@demo.test / password</p>
                    <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                        <a href="/admin" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg font-medium">{{ __('app.landing.admin_panel') }}</a>
                        <a href="http://demo.{{ config('app.central_domain') }}/login" class="px-6 py-2.5 border border-gray-300 rounded-lg font-medium">{{ __('app.landing.demo_login') }}</a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-gray-200 py-6 text-center text-sm text-gray-500">
            {{ config('app.name') }} · MIT · <a href="https://github.com/mohammedelkarsh/laravel-tenant-kit" class="hover:text-gray-800">GitHub</a>
        </footer>
    </body>
</html>
