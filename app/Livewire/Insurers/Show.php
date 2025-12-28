<?php

namespace App\Livewire\Insurers;

use App\Models\Insurer;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Show extends Component
{
    public Insurer $insurer;

    public function mount(Insurer $insurer): void
    {
        $this->insurer = $insurer->loadCount('memberInsurances');
        $this->authorize('view', $insurer);
    }

    public function render(): View
    {
        return view('livewire.insurers.show', [
            'insurer' => $this->insurer,
        ]);
    }
}

