<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <input type="hidden" name="newsletter_locale" value="{{ app()->getLocale() === 'it' ? 'it' : 'en' }}">

            <flux:input name="name" :label="__('Name')" :value="old('name')" type="text" required autofocus autocomplete="name" :placeholder="__('Full name')" />
            <flux:input name="email" :label="__('Email address')" :value="old('email')" type="email" required autocomplete="email" placeholder="email@example.com" />
            <flux:input name="password" :label="__('Password')" type="password" required autocomplete="new-password" :placeholder="__('Password')" passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}" viewable />
            <flux:input name="password_confirmation" :label="__('Confirm password')" type="password" required autocomplete="new-password" :placeholder="__('Confirm password')" passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}" viewable />

            <label class="flex items-start gap-3 rounded-xl border border-zinc-200 p-4 text-sm dark:border-zinc-700 dark:bg-white/[0.02]">
                <input type="hidden" name="newsletter_opt_in" value="0">
                <input type="checkbox" name="newsletter_opt_in" value="1" @checked(old('newsletter_opt_in')) class="mt-0.5 size-4 rounded border-zinc-400 text-[#E10600] focus:ring-[#E10600]">
                <span>
                    <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __('newsletter.register_title') }}</span>
                    <span class="mt-1 block leading-5 text-zinc-500 dark:text-zinc-400">{{ __('newsletter.register_copy') }}</span>
                </span>
            </label>

            <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">{{ __('Create account') }}</flux:button>
        </form>

        <div class="space-x-1 text-center text-sm text-zinc-600 rtl:space-x-reverse dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
