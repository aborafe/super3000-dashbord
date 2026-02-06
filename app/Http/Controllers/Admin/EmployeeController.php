<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class EmployeeController extends Controller
{
    public function index(): View
    {
        $employees = Employee::query()
            ->with('user')
            ->orderByDesc('id')
            ->paginate(15);

        return view('admin.employees.index', [
            'employees' => $employees,
        ]);
    }

    public function create(): View
    {
        return view('admin.employees.create');
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        Employee::query()->create([
            'user_id' => $user->id,
            'job_title' => $data['job_title'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);

        return redirect()
            ->route('admin.employees.index')
            ->with('status', __('Employee created successfully.'));
    }

    public function show(Employee $employee): View
    {
        $employee->load('user');

        return view('admin.employees.show', [
            'employee' => $employee,
        ]);
    }

    public function edit(Employee $employee): View
    {
        $employee->load('user');

        return view('admin.employees.edit', [
            'employee' => $employee,
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $data = $request->validated();

        $user = $employee->user;
        $user->name = $data['name'];
        $user->email = $data['email'];
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        $employee->update([
            'job_title' => $data['job_title'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);

        return redirect()
            ->route('admin.employees.index')
            ->with('status', __('Employee updated successfully.'));
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return redirect()
            ->route('admin.employees.index')
            ->with('status', __('Employee deleted successfully.'));
    }
}
