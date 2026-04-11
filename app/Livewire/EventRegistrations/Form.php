<?php

namespace App\Livewire\EventRegistrations;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Member;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?int $event_id = null;
    public ?int $member_id = null;
    public string $full_name = '';
    public string $phone = '';
    public string $email = '';
    public bool $will_attend = true;
    public string $status = 'confirmed';

    public function mount(): void
    {
        $this->authorize('create', EventRegistration::class);

        $event_from_query = request()->integer('event');
        if ($event_from_query > 0) {
            $this->event_id = $event_from_query;
        }
    }

    public function rules(): array
    {
        return [
            'event_id' => ['required', Rule::exists('events', 'id')],
            'member_id' => ['nullable', Rule::exists('members', 'id')],
            'full_name' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'will_attend' => ['boolean'],
            'status' => ['required', Rule::in(['pending', 'confirmed', 'cancelled', 'attended', 'no_show'])],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $event = Event::query()->findOrFail((int) $validated['event_id']);
        $this->authorize('create', EventRegistration::class);

        if ((int) $event->branch_id !== (int) current_branch_id()) {
            $this->addError('event_id', __('The selected event is not in the current branch.'));
            return;
        }

        if ($validated['member_id']) {
            $member = Member::query()->findOrFail((int) $validated['member_id']);
            $full_name = $member->full_name;
            $phone = $member->phone;
            $email = $member->email;
        } else {
            $full_name = blank($validated['full_name']) ? null : trim($validated['full_name']);
            $phone = blank($validated['phone']) ? null : trim($validated['phone']);
            $email = blank($validated['email']) ? null : trim($validated['email']);
        }

        EventRegistration::create([
            'event_id' => $event->id,
            'branch_id' => $event->branch_id,
            'member_id' => $validated['member_id'] ?: null,
            'full_name' => $full_name,
            'phone' => $phone,
            'email' => $email,
            'will_attend' => (bool) $validated['will_attend'],
            'status' => $validated['status'],
            'registration_datetime' => now(),
        ]);

        session()->flash('success', __('Event registration created successfully.'));
        $this->redirect(route('event-registrations.index'), navigate: true);
    }

    public function render(): View
    {
        $events = Event::query()
            ->orderByDesc('start_datetime')
            ->get(['id', 'title', 'start_datetime']);

        $members = Member::query()
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'member_no', 'phone', 'email']);

        return view('livewire.event-registrations.form', [
            'events' => $events,
            'members' => $members,
        ]);
    }
}

