<?php

namespace App\Services\Inventory;

use App\Models\BranchProduct;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockAdjustment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Increase stock for a branch product.
     */
    public function increaseStock(BranchProduct $branch_product, int $quantity, string $reason, ?int $user_id = null): StockAdjustment
    {
        return DB::transaction(function () use ($branch_product, $quantity, $reason, $user_id) {
            $adjustment = StockAdjustment::create([
                'branch_id' => $branch_product->branch_id,
                'branch_product_id' => $branch_product->id,
                'adjustment_type' => StockAdjustment::TYPE_INCREASE,
                'quantity' => $quantity,
                'reason' => $reason,
                'created_by' => $user_id ?? Auth::id(),
            ]);

            $branch_product->increment('current_stock', $quantity);

            return $adjustment;
        });
    }

    /**
     * Decrease stock for a branch product.
     */
    public function decreaseStock(BranchProduct $branch_product, int $quantity, string $reason, ?int $user_id = null): StockAdjustment
    {
        return DB::transaction(function () use ($branch_product, $quantity, $reason, $user_id) {
            $adjustment = StockAdjustment::create([
                'branch_id' => $branch_product->branch_id,
                'branch_product_id' => $branch_product->id,
                'adjustment_type' => StockAdjustment::TYPE_DECREASE,
                'quantity' => $quantity,
                'reason' => $reason,
                'created_by' => $user_id ?? Auth::id(),
            ]);

            $branch_product->decrement('current_stock', $quantity);

            return $adjustment;
        });
    }

    /**
     * Adjust stock (increase or decrease based on type).
     */
    public function adjustStock(BranchProduct $branch_product, string $type, int $quantity, string $reason): StockAdjustment
    {
        if ($type === StockAdjustment::TYPE_INCREASE) {
            return $this->increaseStock($branch_product, $quantity, $reason);
        }

        return $this->decreaseStock($branch_product, $quantity, $reason);
    }

    /**
     * Receive stock from a purchase order.
     */
    public function receiveFromPurchaseOrder(PurchaseOrder $purchase_order): void
    {
        DB::transaction(function () use ($purchase_order) {
            foreach ($purchase_order->items as $item) {
                // Find or create branch product
                $branch_product = BranchProduct::firstOrCreate(
                    [
                        'branch_id' => $purchase_order->branch_id,
                        'product_id' => $item->product_id,
                    ],
                    [
                        'price' => $item->unit_cost * 1.3, // Default 30% markup
                        'current_stock' => 0,
                    ]
                );

                $this->increaseStock(
                    $branch_product,
                    $item->quantity,
                    "Received from PO #{$purchase_order->order_number}"
                );
            }

            $purchase_order->update(['status' => 'received']);
        });
    }

    /**
     * Process stock deduction for a POS sale.
     */
    public function deductForSale(PosSale $pos_sale): void
    {
        DB::transaction(function () use ($pos_sale) {
            foreach ($pos_sale->items as $item) {
                $this->decreaseStock(
                    $item->branchProduct,
                    $item->quantity,
                    "Sale #{$pos_sale->sale_number}"
                );
            }
        });
    }

    /**
     * Restore stock for a refunded sale.
     */
    public function restoreForRefund(PosSale $pos_sale): void
    {
        DB::transaction(function () use ($pos_sale) {
            foreach ($pos_sale->items as $item) {
                $this->increaseStock(
                    $item->branchProduct,
                    $item->quantity,
                    "Refund for sale #{$pos_sale->sale_number}"
                );
            }
        });
    }

    /**
     * Get low stock products for a branch.
     */
    public function getLowStockProducts(?int $branch_id = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = BranchProduct::with(['product', 'product.category'])
            ->lowStock();

        if ($branch_id) {
            $query->where('branch_id', $branch_id);
        }

        return $query->get();
    }

    /**
     * Get out of stock products for a branch.
     */
    public function getOutOfStockProducts(?int $branch_id = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = BranchProduct::with(['product', 'product.category'])
            ->outOfStock();

        if ($branch_id) {
            $query->where('branch_id', $branch_id);
        }

        return $query->get();
    }

    /**
     * Check if sufficient stock is available.
     */
    public function hasStock(BranchProduct $branch_product, int $quantity): bool
    {
        return $branch_product->current_stock >= $quantity;
    }

    /**
     * Get stock movement history for a branch product.
     */
    public function getStockHistory(BranchProduct $branch_product, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return $branch_product->stockAdjustments()
            ->with('createdBy')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Add product to branch inventory.
     */
    public function addProductToBranch(int $branch_id, int $product_id, float $price, int $initial_stock = 0, ?int $reorder_level = null): BranchProduct
    {
        return DB::transaction(function () use ($branch_id, $product_id, $price, $initial_stock, $reorder_level) {
            $branch_product = BranchProduct::create([
                'branch_id' => $branch_id,
                'product_id' => $product_id,
                'price' => $price,
                'current_stock' => 0,
                'reorder_level' => $reorder_level,
            ]);

            if ($initial_stock > 0) {
                $this->increaseStock($branch_product, $initial_stock, 'Initial stock');
            }

            return $branch_product;
        });
    }

    /**
     * Get inventory value for a branch.
     */
    public function getInventoryValue(?int $branch_id = null): float
    {
        $query = BranchProduct::query();

        if ($branch_id) {
            $query->where('branch_id', $branch_id);
        }

        return $query->selectRaw('SUM(price * current_stock) as total')
            ->value('total') ?? 0;
    }

    /**
     * Get inventory summary for a branch.
     */
    public function getInventorySummary(?int $branch_id = null): array
    {
        $base_query = BranchProduct::query();

        if ($branch_id) {
            $base_query->where('branch_id', $branch_id);
        }

        return [
            'total_products' => (clone $base_query)->count(),
            'total_stock_units' => (clone $base_query)->sum('current_stock'),
            'inventory_value' => $this->getInventoryValue($branch_id),
            'low_stock_count' => (clone $base_query)->lowStock()->count(),
            'out_of_stock_count' => (clone $base_query)->outOfStock()->count(),
        ];
    }
}

