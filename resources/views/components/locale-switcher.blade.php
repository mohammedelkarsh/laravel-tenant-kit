@props(['route' => 'locale.switch'])

@if (count($enabled = \App\Support\Locales::enabled()) > 1)
    <div {{ $attributes->merge(['class' => 'flex items-center gap-2 text-sm']) }}>
        @foreach ($enabled as $code)
            @if ($code === app()->getLocale())
                <span class="font-semibold text-indigo-600">{{ \App\Support\Locales::native($code) }}</span>
            @else
                <a href="{{ route($route, $code) }}" class="text-gray-500 hover:text-gray-800">
                    {{ \App\Support\Locales::native($code) }}
                </a>
            @endif
            @unless ($loop->last)
                <span class="text-gray-300" aria-hidden="true">|</span>
            @endunless
        @endforeach
    </div>
@endif
