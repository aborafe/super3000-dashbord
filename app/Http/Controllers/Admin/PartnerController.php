<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePartnerRequest;
use App\Http\Requests\UpdatePartnerRequest;
use App\Models\Partner;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function index(Request $request): View
    {
        $query = Partner::query();

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($roleType = $request->string('role_type')->toString()) {
            $query->where('role_type', $roleType);
        }

        $partners = $query
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.partners.index', [
            'partners' => $partners,
            'filters' => $request->only(['q', 'role_type']),
        ]);
    }

    public function create(): View
    {
        return view('admin.partners.create');
    }

    public function store(StorePartnerRequest $request): RedirectResponse
    {
        Partner::query()->create($request->validated());

        return redirect()
            ->route('admin.partners.index')
            ->with('status', __('Partner created successfully.'));
    }

    public function show(Partner $partner): View
    {
        $partner->load(['orders', 'debts']);

        return view('admin.partners.show', [
            'partner' => $partner,
        ]);
    }

    public function edit(Partner $partner): View
    {
        return view('admin.partners.edit', [
            'partner' => $partner,
        ]);
    }

    public function update(UpdatePartnerRequest $request, Partner $partner): RedirectResponse
    {
        $partner->update($request->validated());

        return redirect()
            ->route('admin.partners.index')
            ->with('status', __('Partner updated successfully.'));
    }

    public function destroy(Partner $partner): RedirectResponse
    {
        $partner->delete();

        return redirect()
            ->route('admin.partners.index')
            ->with('status', __('Partner deleted successfully.'));
    }
}
