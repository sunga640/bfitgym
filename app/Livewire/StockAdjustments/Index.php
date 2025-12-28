<?php

namespace App\Livewire\StockAdjustments;

use App\Models\BranchProduct;
use App\Models\StockAdjustment;
use App\Services\Inventory\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Stock Adjustments', 'description' => 'Adjust stock levels with tracking.'])]
#[Title('Stock Adjustments')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $type_filter = '';

    // Adjustment modal
    public bool $showModal = false;
    public ?int $branch_product_id = null;
    public string $adjustment_type = StockAdjustment::TYPE_INCREASE;
    public string $quantity = '';
    public string $reason = '';

    protected InventoryService $inventory_service;

    public function boot(InventoryService $inventory_service): void
    {
        $this->inventory_service = $inventory_service;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openAdjustModal(?int $branch_product_id = null): void
    {
        $this->resetForm();
        $this->branch_product_id = $branch_product_id;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->branch_product_id = null;
        $this->adjustment_type = StockAdjustment::TYPE_INCREASE;
        $this->quantity = '';
        $this->reason = '';
        $this->resetErrorBag();
    }

    protected function rules(): array
    {
        return [
            'branch_product_id' => ['required', 'exists:branch_products,id'],
            'adjustment_type' => ['required', 'in:increase,decrease'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function saveAdjustment(): void
    {
        $this->validate();

        try {
            $branch_product = BranchProduct::findOrFail($this->branch_product_id);

            // Validate decrease doesn't go negative
            if ($this->adjustment_type === StockAdjustment::TYPE_DECREASE) {
                if ($branch_product->current_stock < (int) $this->quantity) {
                    session()->flash('error', __('Cannot decrease stock below 0. Current stock: :stock', ['stock' => $branch_product->current_stock]));
                    return;
                }
            }

            $this->inventory_service->adjustStock(
                $branch_product,
                $this->adjustment_type,
                (int) $this->quantity,
                $this->reason
            );

            $this->closeModal();
            session()->flash('success', __('Stock adjusted successfully.'));
        } catch (\Exception $e) {
            Log::error('Failed to adjust stock', ['error' => $e->getMessage()]);
            session()->flash('error', __('Failed to adjust stock. Please try again.'));
        }
    }

    public function render(): View
    {
        $branch_id = current_branch_id();

        $adjustments = StockAdjustment::query()
            ->with(['branchProduct.product', 'createdBy'])
            ->where('branch_id', $branch_id)
            ->when($this->search, function ($query) {
                $query->whereHas('branchProduct.product', function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('sku', 'like', "%{$this->search}%");
                })
                    ->orWhere('reason', 'like', "%{$this->search}%");
            })
            ->when($this->type_filter, fn($query) => $query->where('adjustment_type', $this->type_filter))
            ->latest()
            ->paginate(15);

        // Branch products for dropdown
        $branch_products = BranchProduct::with('product')
            ->where('branch_id', $branch_id)
            ->get()
            ->mapWithKeys(fn($bp) => [
                $bp->id => "{$bp->product->name} ({$bp->product->sku}) - Stock: {$bp->current_stock}"
            ]);

        return view('livewire.stock-adjustments.index', [
            'adjustments' => $adjustments,
            'branch_products' => $branch_products,
        ]);
    }
}

