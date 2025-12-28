<?php

namespace App\Livewire\PurchaseOrders;

use App\Models\PurchaseOrder;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Show extends Component
{
    public PurchaseOrder $purchase_order;

    public function mount(PurchaseOrder $purchaseOrder): void
    {
        $this->purchase_order = $purchaseOrder->load(['supplier', 'items.product']);
    }

    public function printOrder(): void
    {
        $this->dispatch('print-order');
    }

    public function render(): View
    {
        return view('livewire.purchase-orders.show');
    }
}

