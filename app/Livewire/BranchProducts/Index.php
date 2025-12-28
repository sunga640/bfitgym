<?php

namespace App\Livewire\BranchProducts;

use App\Models\BranchProduct;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\Inventory\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Branch Inventory', 'description' => 'Manage stock levels and pricing for your branch.'])]
#[Title('Branch Inventory')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $category_filter = '';

    #[Url]
    public string $stock_filter = '';

    // Add product modal
    public bool $showAddModal = false;
    public ?int $selected_product_id = null;
    public string $new_price = '';
    public string $new_stock = '0';
    public string $new_reorder_level = '';

    // Edit modal
    public bool $showEditModal = false;
    public ?int $editing_id = null;
    public string $edit_price = '';
    public string $edit_reorder_level = '';

    protected InventoryService $inventory_service;

    public function boot(InventoryService $inventory_service): void
    {
        $this->inventory_service = $inventory_service;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openAddModal(): void
    {
        $this->resetAddForm();
        $this->showAddModal = true;
    }

    public function closeAddModal(): void
    {
        $this->showAddModal = false;
        $this->resetAddForm();
    }

    public function resetAddForm(): void
    {
        $this->selected_product_id = null;
        $this->new_price = '';
        $this->new_stock = '0';
        $this->new_reorder_level = '';
        $this->resetErrorBag();
    }

    public function openEditModal(int $id): void
    {
        $branch_product = BranchProduct::findOrFail($id);
        $this->editing_id = $branch_product->id;
        $this->edit_price = (string) $branch_product->price;
        $this->edit_reorder_level = $branch_product->reorder_level !== null ? (string) $branch_product->reorder_level : '';
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editing_id = null;
        $this->edit_price = '';
        $this->edit_reorder_level = '';
        $this->resetErrorBag();
    }

    public function addProduct(): void
    {
        $this->validate([
            'selected_product_id' => ['required', 'exists:products,id'],
            'new_price' => ['required', 'numeric', 'min:0'],
            'new_stock' => ['required', 'integer', 'min:0'],
            'new_reorder_level' => ['nullable', 'integer', 'min:0'],
        ]);

        $branch_id = current_branch_id();

        // Check if already exists
        if (BranchProduct::where('branch_id', $branch_id)->where('product_id', $this->selected_product_id)->exists()) {
            session()->flash('error', __('This product is already in your branch inventory.'));
            return;
        }

        DB::beginTransaction();

        try {
            $this->inventory_service->addProductToBranch(
                $branch_id,
                $this->selected_product_id,
                (float) $this->new_price,
                (int) $this->new_stock,
                $this->new_reorder_level !== '' ? (int) $this->new_reorder_level : null
            );

            DB::commit();
            $this->closeAddModal();
            session()->flash('success', __('Product added to branch inventory.'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to add branch product', ['error' => $e->getMessage()]);
            session()->flash('error', __('Failed to add product. Please try again.'));
        }
    }

    public function updateProduct(): void
    {
        $this->validate([
            'edit_price' => ['required', 'numeric', 'min:0'],
            'edit_reorder_level' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $branch_product = BranchProduct::findOrFail($this->editing_id);
            $branch_product->update([
                'price' => (float) $this->edit_price,
                'reorder_level' => $this->edit_reorder_level !== '' ? (int) $this->edit_reorder_level : null,
            ]);

            $this->closeEditModal();
            session()->flash('success', __('Product updated successfully.'));
        } catch (\Exception $e) {
            Log::error('Failed to update branch product', ['error' => $e->getMessage()]);
            session()->flash('error', __('Failed to update product. Please try again.'));
        }
    }

    public function removeProduct(int $id): void
    {
        try {
            $branch_product = BranchProduct::findOrFail($id);

            // Check if has stock
            if ($branch_product->current_stock > 0) {
                session()->flash('error', __('Cannot remove product with remaining stock. Adjust stock to 0 first.'));
                return;
            }

            $branch_product->delete();
            session()->flash('success', __('Product removed from branch inventory.'));
        } catch (\Exception $e) {
            Log::error('Failed to remove branch product', ['error' => $e->getMessage()]);
            session()->flash('error', __('Failed to remove product. Please try again.'));
        }
    }

    public function render(): View
    {
        $branch_id = current_branch_id();

        $branch_products = BranchProduct::query()
            ->with(['product', 'product.category'])
            ->where('branch_id', $branch_id)
            ->when($this->search, function ($query) {
                $query->whereHas('product', function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('sku', 'like', "%{$this->search}%");
                });
            })
            ->when($this->category_filter, function ($query) {
                $query->whereHas('product', function ($q) {
                    $q->where('product_category_id', $this->category_filter);
                });
            })
            ->when($this->stock_filter, function ($query) {
                match ($this->stock_filter) {
                    'low' => $query->lowStock(),
                    'out' => $query->outOfStock(),
                    'in' => $query->inStock(),
                    default => $query,
                };
            })
            ->latest()
            ->paginate(12);

        // Available products to add (not already in branch)
        $existing_product_ids = BranchProduct::where('branch_id', $branch_id)->pluck('product_id');
        $available_products = Product::active()
            ->whereNotIn('id', $existing_product_ids)
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $categories = ProductCategory::orderBy('name')->get(['id', 'name']);

        // Inventory summary
        $inventory_summary = $this->inventory_service->getInventorySummary($branch_id);

        return view('livewire.branch-products.index', [
            'branch_products' => $branch_products,
            'available_products' => $available_products,
            'categories' => $categories,
            'inventory_summary' => $inventory_summary,
        ]);
    }
}

