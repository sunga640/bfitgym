<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Models\BranchProduct;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Show extends Component
{
    public Product $product;

    public function mount(Product $product): void
    {
        $this->product = $product->load('category');
    }

    public function render(): View
    {
        // Get branch products for this product across all branches
        $branch_products = BranchProduct::with('branch')
            ->where('product_id', $this->product->id)
            ->get();

        // Calculate profit margin if prices are set
        $profit_margin = null;
        $profit_amount = null;
        if ($this->product->buying_price && $this->product->selling_price && $this->product->buying_price > 0) {
            $profit_amount = $this->product->selling_price - $this->product->buying_price;
            $profit_margin = ($profit_amount / $this->product->buying_price) * 100;
        }

        return view('livewire.products.show', [
            'branch_products' => $branch_products,
            'profit_margin' => $profit_margin,
            'profit_amount' => $profit_amount,
        ]);
    }
}

