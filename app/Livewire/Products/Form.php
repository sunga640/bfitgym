<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?Product $product = null;
    public bool $isEditing = false;

    public string $sku = '';
    public string $name = '';
    public ?int $product_category_id = null;
    public string $description = '';
    public ?string $buying_price = null;
    public ?string $selling_price = null;
    public bool $is_active = true;

    public function mount(?Product $product = null): void
    {
        $this->product = $product;
        $this->isEditing = $product && $product->exists;

        if ($this->isEditing) {
            $this->fill(Arr::only($product->toArray(), [
                'sku',
                'name',
                'product_category_id',
                'description',
                'is_active',
            ]));
            $this->description = $product->description ?? '';
            $this->buying_price = $product->buying_price !== null ? (string) $product->buying_price : null;
            $this->selling_price = $product->selling_price !== null ? (string) $product->selling_price : null;
        }
    }

    protected function rules(): array
    {
        return [
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($this->product?->id),
            ],
            'name' => ['required', 'string', 'max:150'],
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'buying_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    public function generateSku(): void
    {
        if (empty($this->name)) {
            return;
        }

        // Generate SKU from name
        $base_sku = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $this->name), 0, 6));
        $suffix = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        $this->sku = $base_sku . '-' . $suffix;
    }

    public function save(): void
    {
        $data = $this->validate();

        DB::beginTransaction();

        try {
            if ($this->isEditing) {
                $this->product->update([
                    'sku' => $data['sku'],
                    'name' => $data['name'],
                    'product_category_id' => $data['product_category_id'],
                    'description' => $data['description'] ?: null,
                    'buying_price' => $data['buying_price'] ?: null,
                    'selling_price' => $data['selling_price'] ?: null,
                    'is_active' => $data['is_active'],
                ]);
                $message = __('Product updated successfully.');
            } else {
                Product::create([
                    'sku' => $data['sku'],
                    'name' => $data['name'],
                    'product_category_id' => $data['product_category_id'],
                    'description' => $data['description'] ?: null,
                    'buying_price' => $data['buying_price'] ?: null,
                    'selling_price' => $data['selling_price'] ?: null,
                    'is_active' => $data['is_active'],
                ]);
                $message = __('Product created successfully.');
            }

            DB::commit();
            session()->flash('success', $message);
            $this->redirect(route('products.index'), navigate: true);
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', __('Failed to save product. Please try again.'));
        }
    }

    public function render(): View
    {
        $categories = ProductCategory::orderBy('name')->get(['id', 'name']);

        return view('livewire.products.form', [
            'categories' => $categories,
        ]);
    }
}

