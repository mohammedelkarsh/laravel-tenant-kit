<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('app.kyc.result_title') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <p class="text-base font-medium text-gray-900">{{ $message }}</p>

                <dl class="grid sm:grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-gray-500">{{ __('app.kyc.status') }}</dt>
                        <dd class="font-medium">{{ $result['status'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('app.kyc.country') }}</dt>
                        <dd class="font-medium uppercase">{{ $result['country'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('app.kyc.level') }}</dt>
                        <dd class="font-medium">{{ $result['level'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('app.kyc.confidence') }}</dt>
                        <dd class="font-medium">{{ $result['confidence'] ?? '—' }}</dd>
                    </div>
                </dl>

                @if (! empty($result['warnings']))
                    <div class="rounded-md bg-amber-50 border border-amber-200 px-3 py-2 text-sm text-amber-900">
                        <p class="font-medium">{{ __('app.kyc.warnings') }}</p>
                        <ul class="list-disc ms-5 mt-1">
                            @foreach ($result['warnings'] as $warning)
                                <li>{{ $warning }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <a href="{{ route('tenant.kyc.onboarding') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                        {{ __('app.kyc.verify_another') }} →
                    </a>
                    @if (($result['status'] ?? null) === 'pending_review')
                        <a href="{{ $reviewUrl }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                            {{ __('app.kyc.open_review') }} →
                        </a>
                    @endif
                    <a href="{{ route('tenant.dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">
                        {{ __('app.kyc.back_dashboard') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-tenant-layout>
