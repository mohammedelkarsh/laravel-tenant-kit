<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locales::direction() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $tenantName }} — {{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900">
        <div class="absolute top-4 end-4">
            <x-locale-switcher route="tenant.locale.switch" />
        </div>
        <div class="min-h-screen flex flex-col items-center justify-center px-6">
            <div class="w-full max-w-md text-center">
                <h1 class="text-3xl font-bold">{{ $tenantName }}</h1>
                <p class="mt-3 text-gray-600 font-mono text-sm" dir="ltr">{{ $tenantId }}.{{ config('app.central_domain') }}</p>
                <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="{{ route('tenant.login') }}" class="px-6 py-2.5 border border-gray-300 rounded-lg font-medium hover:bg-white">{{ __('app.tenant.sign_in') }}</a>
                    <a href="{{ route('tenant.register') }}" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">{{ __('app.tenant.register') }}</a>
                </div>
            </div>
        </div>
    </body>
</html>
