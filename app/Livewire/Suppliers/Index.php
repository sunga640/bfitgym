<?php

namespace App\Livewire\Suppliers;

use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Suppliers', 'description' => 'Manage your product suppliers.'])]
#[Title('Suppliers')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    // Inline modal
    public bool $showModal = false;
    public ?int $editing_id = null;
    public string $name = '';
    public string $contact_person = '';
    public string $phone = '';
    public string $email = '';
    public string $address = '';

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
        $supplier = Supplier::findOrFail($id);
        $this->editing_id = $supplier->id;
        $this->name = $supplier->name;
        $this->contact_person = $supplier->contact_person ?? '';
        $this->phone = $supplier->phone ?? '';
        $this->email = $supplier->email ?? '';
        $this->address = $supplier->address ?? '';
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
        $this->contact_person = '';
        $this->phone = '';
        $this->email = '';
        $this->address = '';
        $this->resetErrorBag();
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        DB::beginTransaction();

        try {
            if ($this->editing_id) {
                $supplier = Supplier::findOrFail($this->editing_id);
                $supplier->update([
                    'name' => $data['name'],
                    'contact_person' => $data['contact_person'] ?: null,
                    'phone' => $data['phone'] ?: null,
                    'email' => $data['email'] ?: null,
                    'address' => $data['address'] ?: null,
                ]);
                $message = __('Supplier updated successfully.');
            } else {
                Supplier::create([
                    'name' => $data['name'],
                    'contact_person' => $data['contact_person'] ?: null,
                    'phone' => $data['phone'] ?: null,
                    'email' => $data['email'] ?: null,
                    'address' => $data['address'] ?: null,
                ]);
                $message = __('Supplier created successfully.');
            }

            DB::commit();
            $this->closeModal();
            session()->flash('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to save supplier', ['error' => $e->getMessage()]);
            session()->flash('error', __('Failed to save supplier. Please try again.'));
        }
    }

    public function delete(int $id): void
    {
        try {
            $supplier = Supplier::findOrFail($id);

            // Check for purchase orders
            if ($supplier->purchaseOrders()->count() > 0) {
                session()->flash('error', __('Cannot delete supplier with existing purchase orders.'));
                return;
            }

            $supplier->delete();
            session()->flash('success', __('Supplier deleted successfully.'));
        } catch (\Exception $e) {
            Log::error('Failed to delete supplier', ['error' => $e->getMessage()]);
            session()->flash('error', __('Failed to delete supplier. Please try again.'));
        }
    }

    public function render(): View
    {
        $suppliers = Supplier::query()
            ->withCount('purchaseOrders')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('contact_person', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->latest()
            ->paginate(12);

        return view('livewire.suppliers.index', [
            'suppliers' => $suppliers,
        ]);
    }
}

