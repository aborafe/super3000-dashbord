@extends('layouts.admin')

@section('title', __('Compose Notification'))

@section('content')
    @php $locale = app()->getLocale(); @endphp
    @php $selectedRecipients = collect(old('recipients', []))->map(fn ($value) => (string) $value)->all(); @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.notifications.send', ['locale' => $locale]) }}">
                    @csrf
                    <div class="row gx-4">
                        <div class="col-lg-7">
                            <h5 class="mb-3">{{ __('Compose Notification') }}</h5>

                            <div class="mb-3">
                                <label class="form-label">{{ __('Title') }}</label>
                                <input type="text" name="title" value="{{ old('title') }}"
                                    class="form-control @error('title') is-invalid @enderror">
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('Message') }}</label>
                                <textarea name="message" class="form-control @error('message') is-invalid @enderror" rows="8">{{ old('message') }}</textarea>
                                @error('message')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <small class="text-muted">{{ __('Select recipients on the right') }}</small>
                                <div>
                                    <button type="button" id="select-none" class="btn btn-sm btn-outline-secondary me-2">{{ __('Clear') }}</button>
                                    <button type="button" id="select-all" class="btn btn-sm btn-outline-primary">{{ __('Select All Visible') }}</button>
                                </div>
                            </div>

                        </div>

                        <div class="col-lg-5">
                            <h6 class="mb-3">{{ __('Recipients') }}</h6>

                            <div class="mb-3">
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <strong>{{ __('Users') }}</strong>
                                    <small class="text-muted">({{ $users->count() }})</small>
                                </div>
                                <input type="text" class="form-control form-control-sm mb-2 recipient-filter" data-target="users" placeholder="{{ __('Search users...') }}">
                                <div class="card border">
                                    <div class="card-body p-2">
                                        <div class="list-group list-group-flush overflow-auto recipient-list" id="users-list" style="max-height:160px;">
                                            @foreach ($users as $user)
                                                <label class="list-group-item d-flex align-items-center mb-1">
                                                    <input class="form-check-input me-2 recipient-checkbox" type="checkbox" name="recipients[]" value="user:{{ $user->id }}" @checked(in_array('user:'.$user->id, $selectedRecipients, true))>
                                                    <div>
                                                        <div class="fw-semibold">{{ $user->name }}</div>
                                                        <small class="text-muted">{{ $user->email }}</small>
                                                    </div>
                                                </label>
                                            @endforeach
                                            <label class="list-group-item d-flex align-items-center mb-0">
                                                <input class="form-check-input me-2 recipient-checkbox" type="checkbox" name="recipients[]" value="all:users" @checked(in_array('all:users', $selectedRecipients, true))>
                                                <div class="fw-semibold">{{ __('All users') }}</div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <strong>{{ __('Roles') }}</strong>
                                    <small class="text-muted">({{ $roles->count() }})</small>
                                </div>
                                <input type="text" class="form-control form-control-sm mb-2 recipient-filter" data-target="roles" placeholder="{{ __('Search roles...') }}">
                                <div class="card border">
                                    <div class="card-body p-2">
                                        <div class="list-group list-group-flush overflow-auto recipient-list" id="roles-list" style="max-height:120px;">
                                            @foreach ($roles as $role)
                                                <label class="list-group-item d-flex align-items-center mb-1">
                                                    <input class="form-check-input me-2 recipient-checkbox" type="checkbox" name="recipients[]" value="role:{{ $role->name }}" @checked(in_array('role:'.$role->name, $selectedRecipients, true))>
                                                    <div class="fw-semibold">{{ $role->name }}</div>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <strong>{{ __('Customers') }}</strong>
                                    <small class="text-muted">({{ $customers->count() }})</small>
                                </div>
                                <input type="text" class="form-control form-control-sm mb-2 recipient-filter" data-target="customers" placeholder="{{ __('Search customers...') }}">
                                <div class="card border">
                                    <div class="card-body p-2">
                                        <div class="list-group list-group-flush overflow-auto recipient-list" id="customers-list" style="max-height:200px;">
                                            @foreach ($customers as $customer)
                                                <label class="list-group-item d-flex align-items-center mb-1">
                                                    <input class="form-check-input me-2 recipient-checkbox"
                                                        type="checkbox"
                                                        name="recipients[]"
                                                        value="customer:{{ $customer->id }}"
                                                        @checked(in_array('customer:'.$customer->id, $selectedRecipients, true))
                                                        @disabled(!$customer->is_active)
                                                        title="{{ !$customer->is_active ? __('Inactive customers cannot receive notifications.') : '' }}">
                                                    <div>
                                                        <div class="fw-semibold">{{ $customer->name }} @if(!$customer->is_active) <span class="text-danger">({{ __('Inactive') }})</span> @endif</div>
                                                        <small class="text-muted">@if(!empty($customer->email)){{ $customer->email }}@elseif(!empty($customer->phone)){{ $customer->phone }}@endif</small>
                                                    </div>
                                                </label>
                                            @endforeach
                                            <label class="list-group-item d-flex align-items-center mb-0">
                                                <input class="form-check-input me-2 recipient-checkbox" type="checkbox" name="recipients[]" value="all:customers" @checked(in_array('all:customers', $selectedRecipients, true))>
                                                <div class="fw-semibold">{{ __('All active customers') }}</div>
                                            </label>
                                            <label class="list-group-item d-flex align-items-center mb-0">
                                                <input class="form-check-input me-2 recipient-checkbox" type="checkbox" name="recipients[]" value="all:everyone" @checked(in_array('all:everyone', $selectedRecipients, true))>
                                                <div class="fw-semibold">{{ __('All users and customers') }}</div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">{{ __('Send') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('page-scripts')
    <script>
        (function () {
            const filters = document.querySelectorAll('.recipient-filter');
            filters.forEach(input => {
                const target = input.dataset.target;
                input.addEventListener('input', function () {
                    const q = this.value.trim().toLowerCase();
                    const list = document.getElementById(target + '-list');
                    if (!list) return;
                    Array.from(list.querySelectorAll('.list-group-item')).forEach(item => {
                        const text = item.innerText.trim().toLowerCase();
                        item.style.display = q === '' || text.includes(q) ? '' : 'none';
                    });
                });
            });

            document.getElementById('select-all')?.addEventListener('click', function () {
                document.querySelectorAll('#users-list .list-group-item, #roles-list .list-group-item, #customers-list .list-group-item').forEach(item => {
                    if (getComputedStyle(item).display === 'none') return;
                    const cb = item.querySelector('.recipient-checkbox');
                    if (cb) cb.checked = true;
                });
            });

            document.getElementById('select-none')?.addEventListener('click', function () {
                document.querySelectorAll('.recipient-checkbox').forEach(cb => cb.checked = false);
            });
        })();
    </script>
@endsection

