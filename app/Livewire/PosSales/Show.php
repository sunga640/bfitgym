<?php

namespace App\Livewire\PosSales;

use App\Models\PosSale;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public PosSale $pos_sale;

    public function mount(PosSale $posSale): void
    {
        $this->pos_sale = $posSale->load([
            'member',
            'items.branchProduct.product',
            'paymentTransaction',
        ]);
    }

    public function printReceipt(): void
    {
        $this->dispatch('print-receipt');
    }

    public function render(): View
    {
        return view('livewire.pos-sales.show', [
            'sale' => $this->pos_sale,
        ]);
    }
}

