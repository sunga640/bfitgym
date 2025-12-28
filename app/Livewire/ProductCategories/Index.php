<?php

namespace App\Livewire\ProductCategories;

use App\Models\ProductCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Product Categories', 'description' => 'Manage product categories for your inventory.'])]
#[Title('Product Categories')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    // Inline create/edit modal
    public bool $showModal = false;
    public ?int $editing_id = null;
    public string $name = '';
    public string $description = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $category = ProductCategory::findOrFail($id);
        $this->editing_id = $category->id;
        $this->name = $category->name;
        $this->description = $category->description ?? '';
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->editing_id = null;
        $this->name = '';
        $this->description = '';
        $this->resetErrorBag();
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        DB::beginTransaction();

        try {
            if ($this->editing_id) {
                $category = ProductCategory::findOrFail($this->editing_id);
                $category->update([
                    'name' => $this->name,
                    'description' => $this->description ?: null,
                ]);
                $message = __('Category updated successfully.');
            } else {
                ProductCategory::create([
                    'name' => $this->name,
                    'description' => $this->description ?: null,
                ]);
                $message = __('Category created successfully.');
            }

            DB::commit();
            $this->closeModal();
            session()->flash('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to save product category', ['error' => $e->getMessage()]);
            session()->flash('error', __('Failed to save category. Please try again.'));
        }
    }

    public function delete(int $id): void
    {
        try {
            $category = ProductCategory::findOrFail($id);

            // Check if category has products
            if ($category->products()->count() > 0) {
                session()->flash('error', __('Cannot delete category with associated products.'));
                return;
            }

            $category->delete();
            session()->flash('success', __('Category deleted successfully.'));
        } catch (\Exception $e) {
            Log::error('Failed to delete product category', ['error' => $e->getMessage()]);
            session()->flash('error', __('Failed to delete category. Please try again.'));
        }
    }

    public function render(): View
    {
        $categories = ProductCategory::query()
            ->withCount('products')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', "%{$this->search}%");
            })
            ->latest()
            ->paginate(12);

        return view('livewire.product-categories.index', [
            'categories' => $categories,
        ]);
    }
}

