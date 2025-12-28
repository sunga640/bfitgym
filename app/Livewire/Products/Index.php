<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Products', 'description' => 'Manage your product catalog.'])]
#[Title('Products')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $category_filter = '';

    #[Url]
    public string $status_filter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        try {
            $product = Product::findOrFail($id);
            $product->update(['is_active' => !$product->is_active]);

            $status = $product->is_active ? __('activated') : __('deactivated');
            session()->flash('success', __('Product :status successfully.', ['status' => $status]));
        } catch (\Exception $e) {
            session()->flash('error', __('Failed to update product status.'));
        }
    }

    public function delete(int $id): void
    {
        try {
            $product = Product::findOrFail($id);

            // Check if product is linked to any branch
            if ($product->branchProducts()->count() > 0) {
                session()->flash('error', __('Cannot delete product that is assigned to branches.'));
                return;
            }

            $product->delete();
            session()->flash('success', __('Product deleted successfully.'));
        } catch (\Exception $e) {
            Log::error('Failed to delete product', ['error' => $e->getMessage()]);
            session()->flash('error', __('Failed to delete product. Please try again.'));
        }
    }

    public function render(): View
    {
        $products = Product::query()
            ->with(['category'])
            ->withCount('branchProducts')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('sku', 'like', "%{$this->search}%");
                });
            })
            ->when($this->category_filter, fn($query) => $query->where('product_category_id', $this->category_filter))
            ->when($this->status_filter !== '', fn($query) => $query->where('is_active', $this->status_filter === '1'))
            ->latest()
            ->paginate(12);

        $categories = ProductCategory::orderBy('name')->get(['id', 'name']);

        return view('livewire.products.index', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }
}

