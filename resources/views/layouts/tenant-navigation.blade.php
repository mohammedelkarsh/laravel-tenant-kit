<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('tenant.dashboard') }}" class="font-semibold text-indigo-600">
                        {{ tenant('name') }}
                    </a>
                </div>

                @auth
                    <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                        <x-nav-link :href="route('tenant.dashboard')" :active="request()->routeIs('tenant.dashboard')">
                            {{ __('Dashboard') }}
                        </x-nav-link>
                        <x-nav-link :href="route('tenant.team.index')" :active="request()->routeIs('tenant.team.*')">
                            {{ __('Team') }}
                        </x-nav-link>
                    </div>
                @endauth
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-4">
                <x-locale-switcher route="tenant.locale.switch" />
                @auth
                    <form method="POST" action="{{ route('tenant.logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-gray-600 hover:text-gray-900">
                            {{ __('Log Out') }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('tenant.login') }}" class="text-sm text-gray-600 hover:text-gray-900 me-4">{{ __('app.nav.log_in') }}</a>
                    <a href="{{ route('tenant.register') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ __('app.tenant.register') }}</a>
                @endauth
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @auth
                <x-responsive-nav-link :href="route('tenant.dashboard')" :active="request()->routeIs('tenant.dashboard')">
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('tenant.team.index')" :active="request()->routeIs('tenant.team.*')">
                    {{ __('Team') }}
                </x-responsive-nav-link>
            @else
                <x-responsive-nav-link :href="route('tenant.login')">
                    {{ __('Log in') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('tenant.register')">
                    {{ __('Register') }}
                </x-responsive-nav-link>
            @endauth
        </div>
    </div>
</nav>
