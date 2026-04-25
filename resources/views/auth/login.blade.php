<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input
                id="email"
                class="block mt-1 w-full"
                type="email"
                name="email"
                :value="old('email')"
                required
                autofocus
                autocomplete="username"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input
                id="password"
                class="block mt-1 w-full"
                type="password"
                name="password"
                required
                autocomplete="current-password"
            />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label class="inline-flex items-center">
                <input
                    type="checkbox"
                    name="remember"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                >
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Remember me') }}
                </span>
            </label>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between mt-6">

            <!-- Left: Register -->
            <div>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}"
                       class="text-sm underline text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                        Don’t have an account? Register
                    </a>
                @endif
            </div>

            <!-- Right: Buttons -->
            <div class="flex items-center gap-4">

                @if (Route::has('password.request'))
                    <a class="text-sm underline text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                       href="{{ route('password.request') }}">
                        Forgot password?
                    </a>
                @endif

                <x-primary-button>
                    {{ __('Log in') }}
                </x-primary-button>

            </div>

        </div>
    </form>
</x-guest-layout>