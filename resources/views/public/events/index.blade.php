<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head', ['title' => __('Public Events')])
</head>
<body class="min-h-screen bg-zinc-100 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h1 class="text-2xl font-semibold">{{ __('Upcoming Public Events') }}</h1>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">
                {{ __('Browse upcoming events and register as a visitor. Paid events are processed securely through PayPal.') }}
            </p>
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300">
                <p class="font-medium">{{ __('Unable to continue:') }}</p>
                <ul class="mt-2 list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @forelse($events as $event)
                <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-3">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $event->title }}</h2>
                        <flux:badge :color="$event->payment_required ? 'amber' : 'emerald'" size="sm">
                            {{ $event->payment_required ? __('Paid') : __('Free') }}
                        </flux:badge>
                    </div>

                    <dl class="mt-4 space-y-2 text-sm text-zinc-600 dark:text-zinc-300">
                        <div>
                            <dt class="inline font-medium">{{ __('Date:') }}</dt>
                            <dd class="inline">{{ $event->start_datetime?->format('M d, Y g:i A') }}</dd>
                        </div>
                        @if($event->end_datetime)
                            <div>
                                <dt class="inline font-medium">{{ __('Ends:') }}</dt>
                                <dd class="inline">{{ $event->end_datetime->format('M d, Y g:i A') }}</dd>
                            </div>
                        @endif
                        @if($event->location)
                            <div>
                                <dt class="inline font-medium">{{ __('Location:') }}</dt>
                                <dd class="inline">{{ $event->location }}</dd>
                            </div>
                        @endif
                        @if($event->capacity)
                            <div>
                                <dt class="inline font-medium">{{ __('Capacity:') }}</dt>
                                <dd class="inline">{{ number_format($event->capacity) }}</dd>
                            </div>
                        @endif
                        @if($event->payment_required)
                            <div>
                                <dt class="inline font-medium">{{ __('Entry fee:') }}</dt>
                                <dd class="inline">{{ money((float) $event->price, $payment_currency) }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if($event->description)
                        <p class="mt-4 line-clamp-3 text-sm text-zinc-600 dark:text-zinc-300">
                            {{ $event->description }}
                        </p>
                    @endif

                    <div class="mt-5">
                        <a
                            href="{{ route('public.events.show', $event) }}"
                            class="inline-flex items-center justify-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300"
                        >
                            {{ __('View and Register') }}
                        </a>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-2xl border border-zinc-200 bg-white p-10 text-center dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 class="text-lg font-semibold">{{ __('No public events are available right now') }}</h2>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Please check back later.') }}</p>
                </div>
            @endforelse
        </div>

        @if($events->hasPages())
            <div class="mt-6 rounded-2xl border border-zinc-200 bg-white px-6 py-4 dark:border-zinc-800 dark:bg-zinc-900">
                {{ $events->links() }}
            </div>
        @endif
    </div>

    @fluxScripts
</body>
</html>
