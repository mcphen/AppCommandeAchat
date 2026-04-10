<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = Category::with('parent')
            ->withCount('articles')
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id'             => $c->id,
                'name'           => $c->name,
                'full_name'      => $c->full_name,
                'parent_id'      => $c->parent_id,
                'parent'         => $c->parent ? ['id' => $c->parent->id, 'name' => $c->parent->name] : null,
                'description'    => $c->description,
                'is_active'      => $c->is_active,
                'articles_count' => $c->articles_count,
                'account_code'   => $c->account_code,
                'account_label'  => $c->account_label,
            ]);

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Categories/Form', [
            'parents' => Category::where('is_active', true)
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'parent_id'     => 'nullable|exists:categories,id',
            'description'   => 'nullable|string|max:1000',
            'is_active'     => 'boolean',
            'account_code'  => 'nullable|string|max:10',
            'account_label' => 'nullable|string|max:255',
        ]);

        // Pas de sous-sous-catégorie : le parent doit être une racine
        if (! empty($data['parent_id'])) {
            $parent = Category::find($data['parent_id']);
            abort_if($parent?->parent_id !== null, 422, 'Impossible de créer plus de deux niveaux de catégorie.');
        }

        Category::create($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Catégorie créée avec succès.');
    }

    public function edit(Category $category): Response
    {
        return Inertia::render('Admin/Categories/Form', [
            'category' => $category,
            'parents'  => Category::where('is_active', true)
                ->whereNull('parent_id')
                ->where('id', '!=', $category->id)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'parent_id'     => 'nullable|exists:categories,id',
            'description'   => 'nullable|string|max:1000',
            'is_active'     => 'boolean',
            'account_code'  => 'nullable|string|max:10',
            'account_label' => 'nullable|string|max:255',
        ]);

        if (! empty($data['parent_id'])) {
            abort_if($data['parent_id'] == $category->id, 422, 'Une catégorie ne peut pas être son propre parent.');
            $parent = Category::find($data['parent_id']);
            abort_if($parent?->parent_id !== null, 422, 'Impossible de créer plus de deux niveaux de catégorie.');
        }

        $category->update($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        abort_if($category->articles()->exists(), 422, 'Cette catégorie contient des articles.');
        abort_if($category->children()->exists(), 422, 'Cette catégorie contient des sous-catégories.');

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Catégorie supprimée.');
    }
}
