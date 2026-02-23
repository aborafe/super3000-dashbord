<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\Product;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $search = request('q');

        $categories = Category::query()
            ->when($search, fn($query) => $query->where('name', 'like', '%' . $search . '%'))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $tableStats = [
            [
                'value' => number_format(Category::query()->count()),
                'label' => __('Total Categories'),
                'icon' => 'bx-list-ul',
            ],
            [
                'value' => number_format(Category::query()->where('is_active', true)->count()),
                'label' => __('Active Categories'),
                'icon' => 'bx-check-circle',
            ],
            [
                'value' => number_format(Category::query()->has('products')->count()),
                'label' => __('Used Categories'),
                'icon' => 'bx-category',
            ],
            [
                'value' => number_format(Product::query()->count()),
                'label' => __('Products Linked'),
                'icon' => 'bx-box',
            ],
        ];

        return view('admin.catalog.categories.index', compact('categories', 'search', 'tableStats'));
    }

    public function create(): View
    {
        return view('admin.catalog.categories.create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug($data['name']);

        $category = Category::query()->create($data);

        // handle cover upload
        if ($request->hasFile('cover_image')) {
            $category->cover_image = \App\Support\ImageUploader::storeCategoryImage($request->file('cover_image'));
            $category->save();
        }

        ActivityLogger::log('created', 'category', $category->id, ['name' => $category->name]);

        return redirect()
            ->route('admin.catalog.categories.index')
            ->with('success', __('Category created successfully.'));
    }

    public function edit(Category $category): View
    {
        return view('admin.catalog.categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();

        if ($category->name !== $data['name']) {
            $data['slug'] = $this->uniqueSlug($data['name'], $category->id);
        }

        $category->update($data);

        // handle cover image replacement
        if ($request->hasFile('cover_image')) {
            $currentCoverImage = $category->getAttribute('cover_image');
            if (is_string($currentCoverImage) && $currentCoverImage !== '') {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($currentCoverImage);
            }
            $category->cover_image = \App\Support\ImageUploader::storeCategoryImage($request->file('cover_image'));
            $category->save();
        }

        ActivityLogger::log('updated', 'category', $category->id, ['name' => $category->name]);

        return redirect()
            ->route('admin.catalog.categories.index')
            ->with('success', __('Category updated successfully.'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        ActivityLogger::log('deleted', 'category', $category->id);

        return redirect()
            ->route('admin.catalog.categories.index')
            ->with('success', __('Category deleted successfully.'));
    }

    public function toggle(Category $category): RedirectResponse
    {
        $category->is_active = ! $category->is_active;
        $category->save();

        ActivityLogger::log('updated', 'category', $category->id, ['is_active' => $category->is_active]);

        return redirect()->back()->with('success', __('Category status updated.'));
    }

    protected function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $counter = 1;

        while (Category::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn($query) => $query->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $original . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
