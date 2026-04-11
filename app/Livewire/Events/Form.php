<?php

namespace App\Livewire\Events;

use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?Event $event = null;

    public bool $is_editing = false;
    public bool $needs_branch_selection = false;

    public string $title = '';
    public string $description = '';
    public string $type = 'public';
    public string $location = '';
    public string $start_date = '';
    public string $start_time = '';
    public string $end_date = '';
    public string $end_time = '';
    public bool $payment_required = false;
    public string $price = '';
    public string $capacity = '';
    public bool $allow_non_members = true;
    public string $status = 'scheduled';

    public function mount(?Event $event = null): void
    {
        $this->event = $event;
        $this->is_editing = $event && $event->exists;

        if ($this->is_editing && $event) {
            $this->authorize('update', $event);

            $this->title = $event->title;
            $this->description = $event->description ?? '';
            $this->type = $event->type;
            $this->location = $event->location ?? '';
            $this->start_date = $event->start_datetime?->format('Y-m-d') ?? now()->format('Y-m-d');
            $this->start_time = $event->start_datetime?->format('H:i') ?? now()->addHour()->format('H:i');
            $this->end_date = $event->end_datetime?->format('Y-m-d') ?? '';
            $this->end_time = $event->end_datetime?->format('H:i') ?? '';
            $this->payment_required = (bool) $event->payment_required;
            $this->price = $event->price !== null ? (string) $event->price : '';
            $this->capacity = $event->capacity !== null ? (string) $event->capacity : '';
            $this->allow_non_members = (bool) $event->allow_non_members;
            $this->status = $event->status;
            return;
        }

        $this->authorize('create', Event::class);

        if (!current_branch_id()) {
            $this->needs_branch_selection = true;
            return;
        }

        $this->start_date = now()->format('Y-m-d');
        $this->start_time = now()->addHour()->format('H:i');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:4000'],
            'type' => ['required', Rule::in(['public', 'paid', 'internal'])],
            'location' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_date' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'payment_required' => ['boolean'],
            'price' => [
                Rule::excludeIf(fn() => !$this->payment_required),
                'required',
                'numeric',
                'min:0.01',
                'max:99999999.99',
            ],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'allow_non_members' => ['boolean'],
            'status' => ['required', Rule::in(['scheduled', 'completed', 'cancelled'])],
        ];
    }

    public function updatedPaymentRequired(bool $is_required): void
    {
        if (!$is_required) {
            $this->price = '';
            $this->resetValidation('price');
        }
    }

    public function save(): void
    {
        if ($this->needs_branch_selection) {
            session()->flash('error', __('Please select a branch before creating an event.'));
            return;
        }

        $validated = $this->validate();

        $start_datetime = \Carbon\Carbon::parse($validated['start_date'] . ' ' . $validated['start_time']);
        $end_datetime = $this->resolveEndDateTime($validated['end_date'] ?? null, $validated['end_time'] ?? null);

        if ($end_datetime && $end_datetime->lt($start_datetime)) {
            $this->addError('end_time', __('End date/time must be after the start date/time.'));
            return;
        }

        $payload = [
            'title' => trim($validated['title']),
            'description' => blank($validated['description']) ? null : trim($validated['description']),
            'type' => $validated['type'],
            'location' => blank($validated['location']) ? null : trim($validated['location']),
            'start_datetime' => $start_datetime,
            'end_datetime' => $end_datetime,
            'payment_required' => (bool) $validated['payment_required'],
            'price' => !empty($validated['payment_required']) ? (float) ($validated['price'] ?? 0) : null,
            'capacity' => blank($validated['capacity']) ? null : (int) $validated['capacity'],
            'allow_non_members' => (bool) $validated['allow_non_members'],
            'status' => $validated['status'],
        ];

        if ($this->is_editing && $this->event) {
            $this->authorize('update', $this->event);
            $this->event->update($payload);
            session()->flash('success', __('Event updated successfully.'));
        } else {
            $this->authorize('create', Event::class);
            $event = Event::create([
                ...$payload,
                'branch_id' => current_branch_id(),
            ]);
            session()->flash('success', __('Event created successfully.'));
            $this->redirect(route('events.show', $event), navigate: true);
            return;
        }

        $this->redirect(route('events.show', $this->event), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.events.form');
    }

    protected function resolveEndDateTime(?string $end_date, ?string $end_time): ?\Carbon\Carbon
    {
        if (blank($end_date) && blank($end_time)) {
            return null;
        }

        $date = blank($end_date) ? $this->start_date : $end_date;
        $time = blank($end_time) ? $this->start_time : $end_time;

        return \Carbon\Carbon::parse($date . ' ' . $time);
    }
}
