<?php

namespace App\Livewire\Insurers;

use App\Models\Insurer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?Insurer $insurer = null;
    public bool $isEditing = false;

    public string $name = '';
    public string $contact_person = '';
    public string $phone = '';
    public string $email = '';
    public string $address = '';
    public string $status = 'active';

    public function mount(?Insurer $insurer = null): void
    {
        $this->insurer = $insurer;
        $this->isEditing = $insurer && $insurer->exists;

        if ($this->isEditing) {
            $this->authorize('update', $insurer);
            $this->fill(Arr::only($insurer->toArray(), [
                'name',
                'contact_person',
                'phone',
                'email',
                'address',
                'status',
            ]));
        } else {
            $this->authorize('create', Insurer::class);
            $this->status = 'active';
        }
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('insurers', 'name')->ignore($this->insurer?->id),
            ],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:150',
                Rule::unique('insurers', 'email')->ignore($this->insurer?->id),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function save(): void
    {
        $this->authorize($this->isEditing ? 'update' : 'create', $this->insurer ?? Insurer::class);
        $data = $this->validate();

        if (!$this->isEditing) {
            Insurer::create($data);
            session()->flash('success', __('Insurer created successfully.'));
            $this->redirect(route('insurers.index'), navigate: true);
        } else {
            $this->insurer->update($data);
            session()->flash('success', __('Insurer updated successfully.'));
            $this->redirect(route('insurers.index'), navigate: true);
        }
    }

    public function render(): View
    {
        return view('livewire.insurers.form');
    }
}

