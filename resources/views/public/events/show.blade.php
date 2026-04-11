<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head', ['title' => $event->title])
</head>
<body class="min-h-screen bg-zinc-100 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <a href="{{ route('public.events.index') }}" class="text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-zinc-100">
                {{ __('Back to Public Events') }}
            </a>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <h1 class="text-2xl font-semibold">{{ $event->title }}</h1>
                <flux:badge :color="$event->payment_required ? 'amber' : 'emerald'" size="sm">
                    {{ $event->payment_required ? __('Payment Required') : __('Free Event') }}
                </flux:badge>
            </div>

            <dl class="mt-5 grid gap-3 text-sm text-zinc-600 dark:text-zinc-300 sm:grid-cols-2">
                <div>
                    <dt class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Starts') }}</dt>
                    <dd>{{ $event->start_datetime?->format('M d, Y g:i A') }}</dd>
                </div>
                @if($event->end_datetime)
                    <div>
                        <dt class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Ends') }}</dt>
                        <dd>{{ $event->end_datetime->format('M d, Y g:i A') }}</dd>
                    </div>
                @endif
                @if($event->location)
                    <div>
                        <dt class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Location') }}</dt>
                        <dd>{{ $event->location }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Attending') }}</dt>
                    <dd>
                        {{ number_format($attending_count) }}
                        @if($event->capacity)
                            / {{ number_format($event->capacity) }}
                        @else
                            {{ __('(Unlimited)') }}
                        @endif
                    </dd>
                </div>
                @if($event->payment_required)
                    <div>
                        <dt class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Entry Fee') }}</dt>
                        <dd>{{ money((float) $event->price, $payment_currency) }} {{ __('via PayPal') }}</dd>
                    </div>
                @endif
            </dl>

            @if($event->description)
                <div class="mt-5 rounded-xl bg-zinc-50 p-4 text-sm text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    {{ $event->description }}
                </div>
            @endif
        </div>

        @if(session('success'))
            <div class="mt-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300">
                <p class="font-medium">{{ __('Please fix the following:') }}</p>
                <ul class="mt-2 list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">{{ __('Register for This Event') }}</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                {{ __('Share your details and attendance preference. If payment is required and you are attending, checkout will continue on PayPal.') }}
            </p>

            @if($is_full)
                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-300">
                    {{ __('This event has reached capacity for attending participants.') }}
                </div>
            @endif

            <form method="POST" action="{{ route('public.events.register', $event) }}" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label for="full_name" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Full Name') }}</label>
                    <input
                        id="full_name"
                        name="full_name"
                        type="text"
                        maxlength="150"
                        value="{{ old('full_name') }}"
                        required
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-zinc-500 focus:ring-2 focus:ring-zinc-400/20 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                    >
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="phone" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Phone') }}</label>
                        <input
                            id="phone"
                            name="phone"
                            type="text"
                            maxlength="50"
                            value="{{ old('phone') }}"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-zinc-500 focus:ring-2 focus:ring-zinc-400/20 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                        >
                    </div>

                    <div>
                        <label for="email" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Email') }}</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            maxlength="100"
                            value="{{ old('email') }}"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-zinc-500 focus:ring-2 focus:ring-zinc-400/20 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                        >
                    </div>
                </div>

                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                    <input type="hidden" name="will_attend" value="0">
                    <label for="will_attend" class="flex cursor-pointer items-start gap-3">
                        <input
                            id="will_attend"
                            name="will_attend"
                            type="checkbox"
                            value="1"
                            @checked(old('will_attend', '1') == '1')
                            class="mt-0.5 h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900"
                        >
                        <span>
                            <span class="block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('I will attend this event') }}</span>
                            <span class="block text-sm text-zinc-600 dark:text-zinc-300">
                                {{ __('Uncheck this if you only want to register interest without taking a seat.') }}
                            </span>
                        </span>
                    </label>
                </div>

                <button
                    type="submit"
                    @disabled($is_full)
                    class="inline-flex items-center justify-center rounded-lg bg-zinc-900 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-zinc-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300"
                >
                    {{ $event->payment_required ? __('Continue to Registration') : __('Submit Registration') }}
                </button>
            </form>
        </div>
    </div>

    @fluxScripts
</body>
</html>
