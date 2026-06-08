<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('app.billing.title', ['name' => $tenant->name]) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (request('checkout') === 'success')
                <div class="p-4 bg-green-50 text-green-800 rounded-lg text-sm">{{ __('app.billing.checkout_success') }}</div>
            @endif

            @unless ($stripeConfigured)
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-amber-900 text-sm">
                    {{ __('app.billing.stripe_not_configured') }}
                </div>
            @endunless

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-gray-600">{{ __('app.billing.workspace') }}: <span class="font-mono text-sm" dir="ltr">{{ $tenant->id }}</span></p>
                @if ($subscription && $subscription->valid())
                    <p class="mt-2 text-green-700 font-medium">{{ __('app.billing.active_subscription', ['price' => $subscription->stripe_price]) }}</p>
                    @if ($stripeConfigured)
                        <a href="{{ route('billing.portal', $tenant) }}" class="inline-block mt-4 text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                            {{ __('app.billing.manage_payment') }}
                        </a>
                    @endif
                @else
                    <p class="mt-2 text-gray-500">{{ __('app.billing.no_subscription') }}</p>
                @endif
            </div>

            <div class="grid sm:grid-cols-2 gap-6">
                @foreach ($plans as $key => $plan)
                    <div class="bg-white shadow-sm sm:rounded-lg p-6 border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">{{ __('app.plans.'.$key.'.name') }}</h3>
                        <p class="mt-2 text-3xl font-bold" dir="ltr">{{ $plan['price'] }}<span class="text-sm font-normal text-gray-500">{{ __('app.billing.per_month') }}</span></p>
                        <p class="mt-3 text-sm text-gray-600">{{ __('app.plans.'.$key.'.description') }}</p>
                        @if ($stripeConfigured && filled($plan['stripe_price']))
                            <form method="POST" action="{{ route('billing.checkout', [$tenant, $key]) }}" class="mt-6">
                                @csrf
                                <x-primary-button>{{ __('app.billing.subscribe') }}</x-primary-button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>

            <a href="{{ $tenant->url() }}" class="text-sm text-indigo-600 hover:text-indigo-800">{{ __('app.billing.back_to_workspace') }}</a>
        </div>
    </div>
</x-app-layout>
