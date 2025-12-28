<?php

namespace App\Livewire\Pos;

use App\Models\BranchProduct;
use App\Models\Member;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\ProductCategory;
use App\Services\Inventory\InventoryService;
use App\Services\Payments\PaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Point of Sale', 'description' => 'Process sales transactions.'])]
#[Title('Point of Sale')]
class Terminal extends Component
{
    // Cart state
    public array $cart = [];
    public ?int $selected_member_id = null;
    public string $member_search = '';
    public float $discount_amount = 0;
    public float $tax_rate = 0; // Percentage
    public string $notes = '';

    // Product filtering
    public string $product_search = '';
    public ?int $category_filter = null;

    // Checkout modal
    public bool $showCheckout = false;
    public string $payment_method = 'cash';
    public string $amount_received = '';
    public string $payment_reference = '';

    // Success modal
    public bool $showSuccess = false;
    public ?PosSale $completed_sale = null;

    protected InventoryService $inventory_service;
    protected PaymentService $payment_service;

    public function boot(InventoryService $inventory_service, PaymentService $payment_service): void
    {
        $this->inventory_service = $inventory_service;
        $this->payment_service = $payment_service;
    }

    public function addToCart(int $branch_product_id): void
    {
        $branch_product = BranchProduct::with('product')->find($branch_product_id);

        if (!$branch_product) {
            return;
        }

        // Check if already in cart
        $cart_key = $this->findCartKey($branch_product_id);

        if ($cart_key !== null) {
            // Check stock before incrementing
            $new_qty = $this->cart[$cart_key]['quantity'] + 1;
            if ($new_qty > $branch_product->current_stock) {
                session()->flash('error', __('Not enough stock available.'));
                return;
            }
            $this->cart[$cart_key]['quantity'] = $new_qty;
            $this->cart[$cart_key]['total'] = $new_qty * $this->cart[$cart_key]['unit_price'];
        } else {
            // Check stock
            if ($branch_product->current_stock < 1) {
                session()->flash('error', __('Product is out of stock.'));
                return;
            }

            // Use product's selling price if available, otherwise fall back to branch product price
            $selling_price = (float) ($branch_product->product->selling_price ?? $branch_product->price);

            $this->cart[] = [
                'branch_product_id' => $branch_product->id,
                'product_name' => $branch_product->product->name,
                'product_sku' => $branch_product->product->sku,
                'unit_price' => $selling_price,
                'quantity' => 1,
                'total' => $selling_price,
                'max_stock' => $branch_product->current_stock,
            ];
        }
    }

    public function updateCartQuantity(int $index, int $quantity): void
    {
        if (!isset($this->cart[$index])) {
            return;
        }

        if ($quantity < 1) {
            $this->removeFromCart($index);
            return;
        }

        // Check stock
        if ($quantity > $this->cart[$index]['max_stock']) {
            session()->flash('error', __('Not enough stock. Maximum: :max', ['max' => $this->cart[$index]['max_stock']]));
            return;
        }

        $this->cart[$index]['quantity'] = $quantity;
        $this->cart[$index]['total'] = $quantity * $this->cart[$index]['unit_price'];
    }

    public function removeFromCart(int $index): void
    {
        if (isset($this->cart[$index])) {
            unset($this->cart[$index]);
            $this->cart = array_values($this->cart);
        }
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->selected_member_id = null;
        $this->member_search = '';
        $this->discount_amount = 0;
        $this->notes = '';
    }

    public function selectMember(int $member_id): void
    {
        $this->selected_member_id = $member_id;
        $this->member_search = '';
    }

    public function clearMember(): void
    {
        $this->selected_member_id = null;
    }

    #[Computed]
    public function subtotal(): float
    {
        return array_sum(array_column($this->cart, 'total'));
    }

    #[Computed]
    public function taxAmount(): float
    {
        return ($this->subtotal - $this->discount_amount) * ($this->tax_rate / 100);
    }

    #[Computed]
    public function total(): float
    {
        return $this->subtotal - $this->discount_amount + $this->taxAmount;
    }

    #[Computed]
    public function changeAmount(): float
    {
        $received = (float) $this->amount_received;
        return max(0, $received - $this->total);
    }

    public function openCheckout(): void
    {
        if (empty($this->cart)) {
            session()->flash('error', __('Cart is empty.'));
            return;
        }

        $this->amount_received = '';
        $this->payment_reference = '';
        $this->showCheckout = true;
    }

    public function closeCheckout(): void
    {
        $this->showCheckout = false;
    }

    public function completeSale(): void
    {
        if (empty($this->cart)) {
            session()->flash('error', __('Cart is empty.'));
            return;
        }

        // Validate payment
        if ($this->payment_method === 'cash' && (float) $this->amount_received < $this->total) {
            session()->flash('error', __('Amount received is less than total.'));
            return;
        }

        DB::beginTransaction();

        try {
            $branch_id = current_branch_id();

            // Generate sale number
            $sale_number = 'INV-' . str_pad($branch_id, 2, '0', STR_PAD_LEFT) . '-' . now()->format('ymdHis') . '-' . str_pad(random_int(1, 999), 3, '0', STR_PAD_LEFT);

            // Create POS sale
            $pos_sale = PosSale::create([
                'branch_id' => $branch_id,
                'member_id' => $this->selected_member_id,
                'sale_number' => $sale_number,
                'sale_datetime' => now(),
                'subtotal' => $this->subtotal,
                'discount_amount' => $this->discount_amount,
                'tax_amount' => $this->taxAmount,
                'total_amount' => $this->total,
                'status' => 'completed',
            ]);

            // Create sale items and deduct stock
            foreach ($this->cart as $item) {
                PosSaleItem::create([
                    'pos_sale_id' => $pos_sale->id,
                    'branch_product_id' => $item['branch_product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total'],
                ]);
            }

            // Deduct stock
            $pos_sale->load('items.branchProduct');
            $this->inventory_service->deductForSale($pos_sale);

            // Record payment
            $payment = $this->payment_service->recordPosSalePayment($pos_sale, [
                'payment_method' => $this->payment_method,
                'reference' => $this->payment_reference ?: null,
                'notes' => $this->notes ?: null,
            ]);

            // Update sale with payment reference
            $pos_sale->update(['payment_transaction_id' => $payment->id]);

            DB::commit();

            // Show success
            $this->completed_sale = $pos_sale->load('items.branchProduct.product', 'member');
            $this->closeCheckout();
            $this->showSuccess = true;

            // Clear cart
            $this->cart = [];
            $this->selected_member_id = null;
            $this->discount_amount = 0;
            $this->notes = '';

            Log::info('POS sale completed', [
                'sale_id' => $pos_sale->id,
                'sale_number' => $sale_number,
                'total' => $this->total,
                'branch_id' => $branch_id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to complete POS sale', ['error' => $e->getMessage()]);
            session()->flash('error', __('Failed to complete sale. Please try again.'));
        }
    }

    public function closeSuccess(): void
    {
        $this->showSuccess = false;
        $this->completed_sale = null;
    }

    public function printReceipt(): void
    {
        // Dispatch browser print event
        $this->dispatch('print-receipt');
    }

    private function findCartKey(int $branch_product_id): ?int
    {
        foreach ($this->cart as $key => $item) {
            if ($item['branch_product_id'] === $branch_product_id) {
                return $key;
            }
        }
        return null;
    }

    public function render(): View
    {
        $branch_id = current_branch_id();

        // Get available products
        $products_query = BranchProduct::with(['product', 'product.category'])
            ->where('branch_id', $branch_id)
            ->whereHas('product', fn($q) => $q->where('is_active', true))
            ->where('current_stock', '>', 0);

        if ($this->product_search) {
            $products_query->whereHas('product', function ($q) {
                $q->where('name', 'like', "%{$this->product_search}%")
                    ->orWhere('sku', 'like', "%{$this->product_search}%");
            });
        }

        if ($this->category_filter) {
            $products_query->whereHas('product', function ($q) {
                $q->where('product_category_id', $this->category_filter);
            });
        }

        $products = $products_query->get();

        // Categories for filter
        $categories = ProductCategory::orderBy('name')->get(['id', 'name']);

        // Members search for assignment
        $searched_members = collect();
        if (strlen($this->member_search) >= 2) {
            $searched_members = Member::where('branch_id', $branch_id)
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->where('first_name', 'like', "%{$this->member_search}%")
                        ->orWhere('last_name', 'like', "%{$this->member_search}%")
                        ->orWhere('member_no', 'like', "%{$this->member_search}%")
                        ->orWhere('phone', 'like', "%{$this->member_search}%");
                })
                ->limit(10)
                ->get(['id', 'first_name', 'last_name', 'member_no', 'phone']);
        }

        // Selected member details
        $selected_member = $this->selected_member_id
            ? Member::find($this->selected_member_id)
            : null;

        // Payment methods
        $payment_methods = PaymentService::getPaymentMethods();

        return view('livewire.pos.terminal', [
            'products' => $products,
            'categories' => $categories,
            'searched_members' => $searched_members,
            'selected_member' => $selected_member,
            'payment_methods' => $payment_methods,
        ]);
    }
}

