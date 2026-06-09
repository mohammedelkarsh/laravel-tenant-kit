@php
    $providers = collect(['google', 'github'])->filter(
        fn (string $provider) => filled(config("services.{$provider}.client_id"))
            && filled(config("services.{$provider}.client_secret"))
    );
@endphp

@if ($providers->isNotEmpty())
    <div class="mt-6">
        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-200"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-2 bg-white text-gray-500">{{ __('app.oauth.or_continue_with') }}</span>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3">
            @foreach ($providers as $provider)
                <a href="{{ route('oauth.redirect', $provider) }}"
                   class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    {{ __('app.oauth.'.$provider) }}
                </a>
            @endforeach
        </div>
    </div>
@endif
