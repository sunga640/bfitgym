<?php

namespace App\Livewire\PurchaseOrders;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Form extends Component
{
    public ?PurchaseOrder $purchase_order = null;
    public bool $isEditing = false;

    public ?int $supplier_id = null;
    public ?string $order_date = null;
    public string $status = 'draft';

    // Order items
    public array $items = [];

    // Add item form
    public ?int $add_product_id = null;
    public string $add_quantity = '1';
    public string $add_unit_cost = '';

    public function mount(?PurchaseOrder $purchaseOrder = null): void
    {
        $this->purchase_order = $purchaseOrder;
        $this->isEditing = $purchaseOrder && $purchaseOrder->exists;

        if ($this->isEditing) {
            $this->supplier_id = $purchaseOrder->supplier_id;
            $this->order_date = $purchaseOrder->order_date?->format('Y-m-d');
            $this->status = $purchaseOrder->status;

            // Load existing items
            $this->items = $purchaseOrder->items->map(fn($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'product_sku' => $item->product->sku,
                'quantity' => $item->quantity,
                'unit_cost' => (float) $item->unit_cost,
                'total_cost' => (float) $item->total_cost,
            ])->toArray();
        } else {
            $this->order_date = now()->format('Y-m-d');
        }
    }

    protected function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'order_date' => ['required', 'date'],
            'status' => ['required', 'in:draft,ordered,received'],
        ];
    }

    public function addItem(): void
    {
        $this->validate([
            'add_product_id' => ['required', 'exists:products,id'],
            'add_quantity' => ['required', 'integer', 'min:1'],
            'add_unit_cost' => ['nullable', 'numeric', 'min:0'],
        ], [], [
            'add_product_id' => 'product',
            'add_quantity' => 'quantity',
            'add_unit_cost' => 'unit cost',
        ]);

        // Check if product already in items
        foreach ($this->items as $item) {
            if ($item['product_id'] == $this->add_product_id) {
                session()->flash('error', __('Product already added to order.'));
                return;
            }
        }

        $product = Product::find($this->add_product_id);
        $quantity = (int) $this->add_quantity;
        // Use entered unit cost, or fall back to product's buying price if available
        $unit_cost = (float) $this->add_unit_cost ?: (float) ($product->buying_price ?? 0);

        $this->items[] = [
            'id' => null,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'quantity' => $quantity,
            'unit_cost' => $unit_cost,
            'total_cost' => $quantity * $unit_cost,
        ];

        // Reset add form
        $this->add_product_id = null;
        $this->add_quantity = '1';
        $this->add_unit_cost = '';
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updateItemQuantity(int $index, int $quantity): void
    {
        if ($quantity < 1) {
            return;
        }

        $this->items[$index]['quantity'] = $quantity;
        $this->items[$index]['total_cost'] = $quantity * $this->items[$index]['unit_cost'];
    }

    public function updateItemUnitCost(int $index, float $unit_cost): void
    {
        if ($unit_cost < 0) {
            return;
        }

        $this->items[$index]['unit_cost'] = $unit_cost;
        $this->items[$index]['total_cost'] = $this->items[$index]['quantity'] * $unit_cost;
    }

    public function getOrderTotalProperty(): float
    {
        return array_sum(array_column($this->items, 'total_cost'));
    }

    public function save(): void
    {
        $this->validate();

        if (empty($this->items)) {
            session()->flash('error', __('Please add at least one item to the order.'));
            return;
        }

        DB::beginTransaction();

        try {
            $branch_id = current_branch_id();
            $total_amount = $this->orderTotal;

            if ($this->isEditing) {
                $this->purchase_order->update([
                    'supplier_id' => $this->supplier_id,
                    'order_date' => $this->order_date,
                    'status' => $this->status,
                    'total_amount' => $total_amount,
                ]);

                // Delete old items and recreate
                $this->purchase_order->items()->delete();
                $order_id = $this->purchase_order->id;
            } else {
                // Generate order number
                $order_number = 'PO-' . str_pad($branch_id, 2, '0', STR_PAD_LEFT) . '-' . now()->format('ymd') . '-' . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);

                $purchase_order = PurchaseOrder::create([
                    'branch_id' => $branch_id,
                    'supplier_id' => $this->supplier_id,
                    'order_number' => $order_number,
                    'order_date' => $this->order_date,
                    'status' => $this->status,
                    'total_amount' => $total_amount,
                ]);

                $order_id = $purchase_order->id;
            }

            // Create items
            foreach ($this->items as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $order_id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'total_cost' => $item['total_cost'],
                ]);
            }

            DB::commit();
            session()->flash('success', $this->isEditing ? __('Purchase order updated successfully.') : __('Purchase order created successfully.'));
            $this->redirect(route('purchase-orders.index'), navigate: true);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to save purchase order', ['error' => $e->getMessage()]);
            session()->flash('error', __('Failed to save purchase order. Please try again.'));
        }
    }

    public function render(): View
    {
        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);
        $products = Product::active()->orderBy('name')->get(['id', 'name', 'sku']);

        return view('livewire.purchase-orders.form', [
            'suppliers' => $suppliers,
            'products' => $products,
            'order_total' => $this->orderTotal,
        ]);
    }
}

