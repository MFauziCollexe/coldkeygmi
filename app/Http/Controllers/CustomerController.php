<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersIndexUrl;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CustomerController extends Controller
{
    use RemembersIndexUrl;

    public function index(Request $request)
    {
        $this->rememberIndexUrl($request, 'customers');

        $query = Customer::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('customer_type')) {
            $query->where('customer_type', $request->customer_type);
        }

        $customers = $query->orderBy('name')->paginate(10)->withQueryString();

        $customers->getCollection()->transform(function ($item) {
            $item->logo_image_url = $item->logo_image ? Storage::url($item->logo_image) : null;
            return $item;
        });

        return Inertia::render('MasterData/Customer/Index', [
            'customers' => $customers,
            'filters' => $request->only(['search', 'customer_type', 'page']),
        ]);
    }

    public function create()
    {
        return Inertia::render('MasterData/Customer/Create');
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);

        if ($request->hasFile('logo_image')) {
            $data['logo_image'] = $request->file('logo_image')->store('customers', 'public');
        } else {
            $data['logo_image'] = null;
        }

        Customer::create($data);

        return $this->redirectToRememberedIndex($request, 'customers', 'customers.index')
            ->with('success', 'Customer created successfully.');
    }

    public function edit(Customer $customer)
    {
        $customer->logo_image_url = $customer->logo_image ? Storage::url($customer->logo_image) : null;
        return Inertia::render('MasterData/Customer/Edit', [
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $this->validatePayload($request, $customer->id);

        if ($request->hasFile('logo_image')) {
            if ($customer->logo_image) {
                Storage::disk('public')->delete($customer->logo_image);
            }
            $data['logo_image'] = $request->file('logo_image')->store('customers', 'public');
        } else {
            $data['logo_image'] = $customer->logo_image;
        }

        $customer->update($data);

        return $this->redirectToRememberedIndex($request, 'customers', 'customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy(Request $request, Customer $customer)
    {
        if ($customer->logo_image) {
            Storage::disk('public')->delete($customer->logo_image);
        }
        $customer->delete();
        return $this->redirectToRememberedIndex($request, 'customers', 'customers.index')
            ->with('success', 'Customer deleted successfully.');
    }

    private function validatePayload(Request $request, ?int $customerId = null): array
    {
        return $request->validate([
            'customer_type' => 'required|in:individual,company',
            'name' => 'required|string|max:255',
            'address_line_1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'zip' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
            'logo_image' => 'nullable|image|max:2048',
        ]);
    }
}
