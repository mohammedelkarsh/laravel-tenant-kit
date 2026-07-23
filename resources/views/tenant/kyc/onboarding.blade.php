<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('app.kyc.onboarding_title') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <p class="text-sm text-gray-600">{{ __('app.kyc.onboarding_intro') }}</p>
                <dl class="grid sm:grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-gray-500">{{ __('app.kyc.country') }}</dt>
                        <dd class="font-medium uppercase">{{ $country }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('app.kyc.level') }}</dt>
                        <dd class="font-medium">{{ $level }}</dd>
                    </div>
                </dl>

                <form method="POST" action="{{ route('tenant.kyc.verify') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="document" :value="__('app.kyc.document')" />
                        <input id="document" name="document" type="file" required
                               class="mt-1 block w-full text-sm text-gray-700"
                               accept=".jpg,.jpeg,.png,.webp,.pdf" />
                        <x-input-error :messages="$errors->get('document')" class="mt-2" />
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="queue" value="1" @checked($queue)
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                        {{ __('app.kyc.queue_label') }}
                    </label>

                    <div class="flex items-center gap-3">
                        <x-primary-button>{{ __('app.kyc.submit') }}</x-primary-button>
                        <a href="{{ route('tenant.dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">
                            {{ __('app.kyc.back_dashboard') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-tenant-layout>
