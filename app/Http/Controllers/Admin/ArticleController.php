<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    public function index(): Response
    {
        $articles = Article::with('category.parent')
            ->withCount('orderLines')
            ->orderBy('name')
            ->get()
            ->map(fn ($a) => [
                'id'               => $a->id,
                'name'             => $a->name,
                'reference'        => $a->reference,
                'description'      => $a->description,
                'unit'             => $a->unit,
                'unit_price'       => $a->unit_price,
                'is_active'        => $a->is_active,
                'nature'           => $a->nature,
                'order_lines_count' => $a->order_lines_count,
                'category'         => $a->category ? [
                    'id'       => $a->category->id,
                    'name'     => $a->category->name,
                    'full_name' => $a->category->full_name,
                ] : null,
            ]);

        return Inertia::render('Admin/Articles/Index', [
            'articles'   => $articles,
            'categories' => $this->getCategoryList(),
            'units'      => $this->getUnits(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Articles/Form', [
            'categories' => $this->getCategoryList(),
            'units'      => $this->getUnits(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'name'        => 'required|string|max:255',
            'reference'   => 'nullable|string|max:100|unique:articles,reference',
            'description' => 'nullable|string|max:1000',
            'unit'        => 'required|string|max:50',
            'unit_price'  => 'nullable|numeric|min:0',
            'is_active'   => 'boolean',
            'nature'      => 'required|in:interne,achat',
        ]);

        Article::create($data);

        return redirect()->route('admin.articles.index')
            ->with('success', 'Article créé avec succès.');
    }

    public function edit(Article $article): Response
    {
        return Inertia::render('Admin/Articles/Form', [
            'article'    => $article,
            'categories' => $this->getCategoryList(),
            'units'      => $this->getUnits(),
        ]);
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'name'        => 'required|string|max:255',
            'reference'   => 'nullable|string|max:100|unique:articles,reference,' . $article->id,
            'description' => 'nullable|string|max:1000',
            'unit'        => 'required|string|max:50',
            'unit_price'  => 'nullable|numeric|min:0',
            'is_active'   => 'boolean',
            'nature'      => 'required|in:interne,achat',
        ]);

        $article->update($data);

        return redirect()->route('admin.articles.index')
            ->with('success', 'Article mis à jour.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        abort_if($article->orderLines()->exists(), 422, 'Cet article est utilisé dans des lignes de commande.');

        $article->delete();

        return redirect()->route('admin.articles.index')
            ->with('success', 'Article supprimé.');
    }

    private function getCategoryList(): \Illuminate\Support\Collection
    {
        return Category::with('parent')
            ->where('is_active', true)
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id'       => $c->id,
                'name'     => $c->name,
                'full_name' => $c->full_name,
                'parent_id' => $c->parent_id,
            ]);
    }

    private function getUnits(): array
    {
        return ['pièce', 'kg', 'litre', 'mètre', 'boîte', 'carton', 'heure', 'forfait', 'lot'];
    }
}
