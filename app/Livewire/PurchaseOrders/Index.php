<?php

namespace App\Livewire\PurchaseOrders;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\Inventory\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Purchase Orders', 'description' => 'Manage purchase orders and receiving.'])]
#[Title('Purchase Orders')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status_filter = '';

    #[Url]
    public string $supplier_filter = '';

    protected InventoryService $inventory_service;

    public function boot(InventoryService $inventory_service): void
    {
        $this->inventory_service = $inventory_service;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function receiveOrder(int $id): void
    {
        try {
            $purchase_order = PurchaseOrder::with('items')->findOrFail($id);

            if ($purchase_order->status !== 'ordered') {
                session()->flash('error', __('Only orders with "ordered" status can be received.'));
                return;
            }

            $this->inventory_service->receiveFromPurchaseOrder($purchase_order);
            session()->flash('success', __('Purchase order received and stock updated.'));
        } catch (\Exception $e) {
            Log::error('Failed to receive purchase order', ['error' => $e->getMessage()]);
            session()->flash('error', __('Failed to receive order. Please try again.'));
        }
    }

    public function delete(int $id): void
    {
        try {
            $purchase_order = PurchaseOrder::findOrFail($id);

            if ($purchase_order->status === 'received') {
                session()->flash('error', __('Cannot delete received purchase orders.'));
                return;
            }

            $purchase_order->items()->delete();
            $purchase_order->delete();
            session()->flash('success', __('Purchase order deleted successfully.'));
        } catch (\Exception $e) {
            Log::error('Failed to delete purchase order', ['error' => $e->getMessage()]);
            session()->flash('error', __('Failed to delete order. Please try again.'));
        }
    }

    public function render(): View
    {
        $branch_id = current_branch_id();

        $orders = PurchaseOrder::query()
            ->with(['supplier', 'items'])
            ->where('branch_id', $branch_id)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('order_number', 'like', "%{$this->search}%")
                        ->orWhereHas('supplier', function ($q) {
                            $q->where('name', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->status_filter, fn($query) => $query->where('status', $this->status_filter))
            ->when($this->supplier_filter, fn($query) => $query->where('supplier_id', $this->supplier_filter))
            ->latest()
            ->paginate(12);

        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);

        return view('livewire.purchase-orders.index', [
            'orders' => $orders,
            'suppliers' => $suppliers,
        ]);
    }
}

