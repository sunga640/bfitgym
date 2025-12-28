<?php

namespace App\Livewire\PosSales;

use App\Models\PosSale;
use App\Services\Inventory\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'POS Sales History', 'description' => 'View and manage completed sales.'])]
#[Title('POS Sales History')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status_filter = '';

    #[Url]
    public string $date_from = '';

    #[Url]
    public string $date_to = '';

    protected InventoryService $inventory_service;

    public function boot(InventoryService $inventory_service): void
    {
        $this->inventory_service = $inventory_service;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function refundSale(int $id): void
    {
        DB::beginTransaction();

        try {
            $sale = PosSale::with('items.branchProduct')->findOrFail($id);

            if ($sale->status !== 'completed') {
                session()->flash('error', __('Only completed sales can be refunded.'));
                return;
            }

            // Restore stock
            $this->inventory_service->restoreForRefund($sale);

            // Update sale status
            $sale->update(['status' => 'refunded']);

            // Update payment transaction if exists
            if ($sale->paymentTransaction) {
                $sale->paymentTransaction->update([
                    'status' => 'failed',
                    'notes' => ($sale->paymentTransaction->notes ?? '') . "\n[REFUNDED] " . now()->toDateTimeString(),
                ]);
            }

            DB::commit();

            Log::info('POS sale refunded', [
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'user_id' => auth()->id(),
            ]);

            session()->flash('success', __('Sale refunded and stock restored.'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to refund sale', ['error' => $e->getMessage()]);
            session()->flash('error', __('Failed to refund sale. Please try again.'));
        }
    }

    public function render(): View
    {
        $branch_id = current_branch_id();

        $sales = PosSale::query()
            ->with(['member', 'items', 'paymentTransaction'])
            ->where('branch_id', $branch_id)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('sale_number', 'like', "%{$this->search}%")
                        ->orWhereHas('member', function ($q) {
                            $q->where('first_name', 'like', "%{$this->search}%")
                                ->orWhere('last_name', 'like', "%{$this->search}%")
                                ->orWhere('phone', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->status_filter, fn($query) => $query->where('status', $this->status_filter))
            ->when($this->date_from, fn($query) => $query->whereDate('sale_datetime', '>=', $this->date_from))
            ->when($this->date_to, fn($query) => $query->whereDate('sale_datetime', '<=', $this->date_to))
            ->latest('sale_datetime')
            ->paginate(15);

        // Calculate totals for the filtered results
        $totals_query = PosSale::where('branch_id', $branch_id)
            ->when($this->status_filter, fn($query) => $query->where('status', $this->status_filter))
            ->when($this->date_from, fn($query) => $query->whereDate('sale_datetime', '>=', $this->date_from))
            ->when($this->date_to, fn($query) => $query->whereDate('sale_datetime', '<=', $this->date_to));

        $totals = [
            'count' => (clone $totals_query)->count(),
            'revenue' => (clone $totals_query)->where('status', 'completed')->sum('total_amount'),
            'refunded' => (clone $totals_query)->where('status', 'refunded')->sum('total_amount'),
        ];

        return view('livewire.pos-sales.index', [
            'sales' => $sales,
            'totals' => $totals,
        ]);
    }
}

