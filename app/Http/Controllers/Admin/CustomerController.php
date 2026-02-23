<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\StoreLedgerPaymentRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Services\CustomerLedgerService;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        $search = request('q');

        $customers = Customer::query()
            ->when($search, fn ($query) => $query->where('name', 'like', '%' . $search . '%'))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $tableStats = [
            [
                'value' => number_format(Customer::query()->count()),
                'label' => __('Total Customers'),
                'icon' => 'bx-group',
            ],
            [
                'value' => number_format(Customer::query()->where('is_active', true)->count()),
                'label' => __('Active Customers'),
                'icon' => 'bx-user-check',
            ],
            [
                'value' => number_format(Customer::query()->has('orders')->count()),
                'label' => __('Customers With Orders'),
                'icon' => 'bx-cart',
            ],
            [
                'value' => number_format(Order::query()->count()),
                'label' => __('Total Orders'),
                'icon' => 'bx-receipt',
            ],
        ];

        return view('admin.sales.customers.index', compact('customers', 'search', 'tableStats'));
    }

    public function create(): View
    {
        return view('admin.sales.customers.create');
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = (bool) $data['is_active'];
        $data['password'] = Hash::make((string) $data['password']);

        $customer = Customer::query()->create($data);

        ActivityLogger::log('created', 'customer', $customer->id, ['name' => $customer->name]);

        return redirect()
            ->route('admin.sales.customers.index')
            ->with('success', __('Customer created successfully.'));
    }

    public function edit(Customer $customer): View
    {
        $ledgerService = app(CustomerLedgerService::class);
        $ledger = $ledgerService->buildCustomerLedger($customer);

        return view('admin.sales.customers.edit', compact('customer', 'ledger'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = (bool) $data['is_active'];

        if (! empty($data['password'])) {
            $data['password'] = Hash::make((string) $data['password']);
        } else {
            unset($data['password']);
        }

        $customer->update($data);

        ActivityLogger::log('updated', 'customer', $customer->id, ['name' => $customer->name]);

        return redirect()
            ->route('admin.sales.customers.index')
            ->with('success', __('Customer updated successfully.'));
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        ActivityLogger::log('deleted', 'customer', $customer->id);

        return redirect()
            ->route('admin.sales.customers.index')
            ->with('success', __('Customer deleted successfully.'));
    }

    public function storePayment(
        StoreLedgerPaymentRequest $request,
        Customer $customer
    ): RedirectResponse {
        $validated = $request->validated();
        $ledgerService = app(CustomerLedgerService::class);
        $authIdentifier = $request->user()?->getAuthIdentifier();
        $createdBy = is_numeric($authIdentifier) ? (int) $authIdentifier : null;

        $payment = $ledgerService->postCustomerPayment(
            $customer,
            (float) $validated['amount'],
            (string) $validated['method'],
            isset($validated['paid_at']) ? Carbon::parse((string) $validated['paid_at']) : null,
            isset($validated['notes']) ? (string) $validated['notes'] : null,
            $createdBy
        );

        ActivityLogger::log('created', 'payment', $payment->id, [
            'source' => 'customer_account',
            'customer_id' => $customer->id,
            'amount' => (float) $payment->amount,
        ]);

        return redirect()
            ->route('admin.sales.customers.edit', [
                'locale' => app()->getLocale(),
                'customer' => $customer,
            ])
            ->with('success', __('Payment added and allocated successfully.'));
    }

    public function ledger(Request $request, Customer $customer): JsonResponse
    {
        $ledgerService = app(CustomerLedgerService::class);
        $ordersLimit = max(10, min(300, (int) $request->query('orders_limit', 100)));
        $paymentsLimit = max(10, min(300, (int) $request->query('payments_limit', 100)));

        return response()->json([
            'status' => true,
            'data' => $ledgerService->buildCustomerLedger($customer, $ordersLimit, $paymentsLimit),
        ]);
    }
}
