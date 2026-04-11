<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head', ['title' => __('Registration Complete')])
</head>
<body class="min-h-screen bg-zinc-100 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <div class="mx-auto flex min-h-screen max-w-3xl items-center px-4 py-10 sm:px-6 lg:px-8">
        <div class="w-full rounded-2xl border border-zinc-200 bg-white p-8 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                <flux:icon name="check" class="h-6 w-6" />
            </div>

            <h1 class="text-2xl font-semibold">{{ __('Registration Submitted') }}</h1>

            @if(session('success'))
                <p class="mt-2 text-sm text-emerald-700 dark:text-emerald-300">{{ session('success') }}</p>
            @else
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                    {{ __('Your event registration has been recorded successfully.') }}
                </p>
            @endif

            <dl class="mt-6 space-y-3 text-sm text-zinc-700 dark:text-zinc-200">
                <div>
                    <dt class="inline font-medium">{{ __('Event:') }}</dt>
                    <dd class="inline">{{ $event->title }}</dd>
                </div>
                <div>
                    <dt class="inline font-medium">{{ __('Schedule:') }}</dt>
                    <dd class="inline">{{ $event->start_datetime?->format('M d, Y g:i A') }}</dd>
                </div>
                <div>
                    <dt class="inline font-medium">{{ __('Registrant:') }}</dt>
                    <dd class="inline">{{ $registration->registrant_name }}</dd>
                </div>
                <div>
                    <dt class="inline font-medium">{{ __('Attendance:') }}</dt>
                    <dd class="inline">{{ $registration->will_attend ? __('Will attend') : __('Not attending') }}</dd>
                </div>
                <div>
                    <dt class="inline font-medium">{{ __('Status:') }}</dt>
                    <dd class="inline">{{ ucfirst(str_replace('_', ' ', $registration->status)) }}</dd>
                </div>
                @if($registration->paymentTransaction)
                    <div>
                        <dt class="inline font-medium">{{ __('Payment:') }}</dt>
                        <dd class="inline">
                            {{ money((float) $registration->paymentTransaction->amount, $registration->paymentTransaction->currency ?: $payment_currency) }}
                            - {{ ucfirst($registration->paymentTransaction->status) }}
                        </dd>
                    </div>
                @endif
            </dl>

            <div class="mt-8 flex flex-wrap gap-3">
                <a
                    href="{{ route('public.events.index') }}"
                    class="inline-flex items-center justify-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300"
                >
                    {{ __('Browse Other Events') }}
                </a>
                <a
                    href="{{ route('public.events.show', $event) }}"
                    class="inline-flex items-center justify-center rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                    {{ __('Back to Event') }}
                </a>
            </div>
        </div>
    </div>

    @fluxScripts
</body>
</html>
