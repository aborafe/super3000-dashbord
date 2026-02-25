@extends('layouts.admin')

@section('title', __('Edit Product'))

@section('content')
    @php
        /** @var \Illuminate\Support\ViewErrorBag $viewErrors */
        $viewErrors = $errors instanceof \Illuminate\Support\ViewErrorBag ? $errors : new \Illuminate\Support\ViewErrorBag();
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold py-3 mb-0">{{ __('Edit Product') }}</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1 mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">{{ __('Products') }}</a>
                        </li>
                        <li class="breadcrumb-item active">{{ __('Edit') }}</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST"
                    action="{{ route('admin.products.update', ['locale' => app()->getLocale(), 'product' => $product->id]) }}"
                    class="row g-3" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Name') }}</label>
                        <input type="text" name="name" value="{{ old('name', $product->name) }}"
                            class="form-control @error('name') is-invalid @enderror">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('SKU') }}</label>
                        <input type="text" name="sku" value="{{ old('sku', $product->sku) }}"
                            class="form-control @error('sku') is-invalid @enderror">
                        @error('sku')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Price') }}</label>
                        <input type="number" step="0.01" name="price" value="{{ old('price', $product->price) }}"
                            class="form-control @error('price') is-invalid @enderror">
                        @error('price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Stock Qty') }}</label>
                        <input type="number" name="stock_qty" value="{{ old('stock_qty', $product->stock_qty) }}"
                            class="form-control @error('stock_qty') is-invalid @enderror">
                        @error('stock_qty')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Category') }}</label>
                        <select name="category_id" class="form-select @error('category_id') is-invalid @enderror">
                            <option value="">{{ __('Select Category') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Brand') }}</label>
                        <input type="text" name="brand" value="{{ old('brand', $product->brand) }}"
                            class="form-control @error('brand') is-invalid @enderror">
                        @error('brand')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Made In') }}</label>
                        <input type="text" name="made_in" value="{{ old('made_in', $product->made_in) }}"
                            class="form-control @error('made_in') is-invalid @enderror">
                        @error('made_in')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Status') }}</label>
                        <div class="form-check form-switch mt-2">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active_edit"
                                value="1" @checked(old('is_active', $product->is_active))>
                            <label class="form-check-label" for="is_active_edit">{{ __('Active') }}</label>
                        </div>
                        @error('is_active')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('Description') }}</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="4">{{ old('description', $product->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    @php
                        $removeCoverOld = old('remove_cover', '0') === '1';
                        $deletedImageIdsOld = collect(old('deleted_image_ids', []))
                            ->map(fn ($id) => (int) $id)
                            ->all();
                    @endphp
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Cover Image') }}</label>
                        <input type="hidden" name="remove_cover" id="removeProductCoverInput"
                            value="{{ $removeCoverOld ? '1' : '0' }}">
                        @if ($product->cover_image)
                            <div class="mb-2" id="productCoverWrapper">
                                <img src="{{ asset('storage/' . $product->cover_image) }}" alt="cover"
                                    style="max-height:100px;" class="rounded border">
                                <div class="d-flex align-items-center gap-2 mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                        id="removeProductCoverBtn">
                                        {{ $removeCoverOld ? __('Undo remove') : __('Remove image') }}
                                    </button>
                                    <label for="coverImageInput" class="btn btn-sm btn-outline-primary mb-0">
                                        {{ __('Change image') }}
                                    </label>
                                    <span class="badge bg-label-danger {{ $removeCoverOld ? '' : 'd-none' }}"
                                        id="removeProductCoverBadge">{{ __('Will be removed on save') }}</span>
                                </div>
                            </div>
                        @endif
                        <input type="file" name="cover_image" id="coverImageInput"
                            class="form-control @error('cover_image') is-invalid @enderror">
                        @error('cover_image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Additional Images') }}</label>
                        <input type="file" name="images[]" id="additionalImagesInput" multiple
                            class="form-control @error('images') is-invalid @enderror">
                        @if ($viewErrors->has('images.*'))
                            <div class="invalid-feedback d-block">
                                {{ $viewErrors->first('images.*') }}
                            </div>
                        @endif


                    </div>
                    @if ($product->images->isNotEmpty())
                        <div class="col-12">
                            <label class="form-label">{{ __('Existing Images') }}</label>
                            <div class="row">
                                @foreach ($product->images as $img)
                                    @php
                                        $isMarkedForDelete = in_array((int) $img->id, $deletedImageIdsOld, true);
                                    @endphp
                                    <div class="col-auto mb-2" style="position:relative;">
                                        <img src="{{ asset('storage/' . $img->image_path) }}"
                                            style="max-height:80px;" class="rounded border" />
                                        <input type="hidden" name="images_orders[{{ $loop->index }}][id]"
                                            value="{{ $img->id }}">
                                        <input type="number" name="images_orders[{{ $loop->index }}][sort_order]"
                                            value="{{ old('images_orders.' . $loop->index . '.sort_order', $img->sort_order) }}"
                                            class="form-control form-control-sm mt-1" style="width:60px;"
                                            placeholder="#">
                                        <div class="d-flex align-items-center gap-2 mt-2">
                                            <input class="d-none" type="checkbox" name="deleted_image_ids[]"
                                                value="{{ $img->id }}" id="del-img-{{ $img->id }}"
                                                @checked($isMarkedForDelete)>
                                            <button type="button"
                                                class="btn btn-sm {{ $isMarkedForDelete ? 'btn-danger' : 'btn-outline-danger' }} js-toggle-image-delete"
                                                data-target="del-img-{{ $img->id }}"
                                                data-badge="del-img-badge-{{ $img->id }}">
                                                {{ $isMarkedForDelete ? __('Undo remove') : __('Remove image') }}
                                            </button>
                                            <span id="del-img-badge-{{ $img->id }}"
                                                class="badge bg-label-danger {{ $isMarkedForDelete ? '' : 'd-none' }}">
                                                {{ __('Will be removed on save') }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                        <a href="{{ route('admin.products.index') }}"
                            class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const coverRemoveButton = document.getElementById('removeProductCoverBtn');
            const coverRemoveInput = document.getElementById('removeProductCoverInput');
            const coverRemoveBadge = document.getElementById('removeProductCoverBadge');

            if (coverRemoveButton && coverRemoveInput && coverRemoveBadge) {
                const syncCoverButton = function() {
                    const isMarked = coverRemoveInput.value === '1';
                    coverRemoveButton.classList.toggle('btn-danger', isMarked);
                    coverRemoveButton.classList.toggle('btn-outline-danger', !isMarked);
                    coverRemoveBadge.classList.toggle('d-none', !isMarked);
                    coverRemoveButton.textContent = isMarked ?
                        @json(__('Undo remove')) :
                        @json(__('Remove image'));
                };

                coverRemoveButton.addEventListener('click', function() {
                    coverRemoveInput.value = coverRemoveInput.value === '1' ? '0' : '1';
                    syncCoverButton();
                });

                syncCoverButton();
            }

            document.querySelectorAll('.js-toggle-image-delete').forEach(function(button) {
                const checkboxId = button.getAttribute('data-target');
                const badgeId = button.getAttribute('data-badge');
                const checkbox = checkboxId ? document.getElementById(checkboxId) : null;
                const badge = badgeId ? document.getElementById(badgeId) : null;

                if (!checkbox || !badge) {
                    return;
                }

                const syncImageButton = function() {
                    const isMarked = checkbox.checked;
                    button.classList.toggle('btn-danger', isMarked);
                    button.classList.toggle('btn-outline-danger', !isMarked);
                    badge.classList.toggle('d-none', !isMarked);
                    button.textContent = isMarked ? @json(__('Undo remove')) : @json(__('Remove image'));
                };

                button.addEventListener('click', function() {
                    checkbox.checked = !checkbox.checked;
                    syncImageButton();
                });

                syncImageButton();
            });
        });
    </script>
@endsection
