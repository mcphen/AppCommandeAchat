<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use App\Models\Budget;
use App\Models\Category;
use App\Services\BudgetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function __construct(private BudgetService $budgetService) {}

    public function index(Request $request): Response
    {
        $year = $request->integer('year', now()->year);

        $budgets = Budget::with(['boutique', 'category'])
            ->where('year', $year)
            ->orderBy('month')
            ->orderBy('boutique_id')
            ->orderBy('category_id')
            ->get()
            ->map(fn ($b) => [
                'id'          => $b->id,
                'boutique'    => $b->boutique ? ['id' => $b->boutique->id, 'name' => $b->boutique->name, 'code' => $b->boutique->code] : null,
                'category'    => $b->category ? ['id' => $b->category->id, 'name' => $b->category->name] : null,
                'year'        => $b->year,
                'month'       => $b->month,
                'period'      => $b->period_label,
                'amount'      => (float) $b->amount,
                'notes'       => $b->notes,
                'consumption' => $this->budgetService->computeConsumption($b),
            ]);

        $availableYears = $this->getAvailableYears();

        return Inertia::render('Admin/Budgets/Index', [
            'budgets'        => $budgets,
            'selectedYear'   => $year,
            'availableYears' => $availableYears,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Budgets/Form', [
            'boutiques'  => $this->getBoutiqueList(),
            'categories' => $this->getCategoryList(),
            'years'      => $this->getYears(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'boutique_id' => 'nullable|exists:boutiques,id',
            'category_id' => 'nullable|exists:categories,id',
            'year'        => 'required|integer|min:2020|max:2050',
            'month'       => 'nullable|integer|min:1|max:12',
            'amount'      => 'required|numeric|min:1',
            'notes'       => 'nullable|string|max:1000',
        ]);

        $this->checkUniqueness($data);

        $data['created_by'] = auth()->id();
        Budget::create($data);

        return redirect()->route('admin.budgets.index')
            ->with('success', 'Budget créé avec succès.');
    }

    public function edit(Budget $budget): Response
    {
        return Inertia::render('Admin/Budgets/Form', [
            'budget' => [
                'id'          => $budget->id,
                'boutique_id' => $budget->boutique_id,
                'category_id' => $budget->category_id,
                'year'        => $budget->year,
                'month'       => $budget->month,
                'amount'      => (float) $budget->amount,
                'notes'       => $budget->notes,
            ],
            'boutiques'  => $this->getBoutiqueList(),
            'categories' => $this->getCategoryList(),
            'years'      => $this->getYears(),
        ]);
    }

    public function update(Request $request, Budget $budget): RedirectResponse
    {
        $data = $request->validate([
            'boutique_id' => 'nullable|exists:boutiques,id',
            'category_id' => 'nullable|exists:categories,id',
            'year'        => 'required|integer|min:2020|max:2050',
            'month'       => 'nullable|integer|min:1|max:12',
            'amount'      => 'required|numeric|min:1',
            'notes'       => 'nullable|string|max:1000',
        ]);

        $this->checkUniqueness($data, $budget->id);

        $budget->update($data);

        return redirect()->route('admin.budgets.index')
            ->with('success', 'Budget mis à jour.');
    }

    public function destroy(Budget $budget): RedirectResponse
    {
        $budget->delete();

        return redirect()->route('admin.budgets.index')
            ->with('success', 'Budget supprimé.');
    }

    private function checkUniqueness(array $data, ?int $excludeId = null): void
    {
        $query = Budget::where('boutique_id', $data['boutique_id'] ?? null)
            ->where('category_id', $data['category_id'] ?? null)
            ->where('year', $data['year'])
            ->where('month', $data['month'] ?? null);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            abort(422, 'Un budget existe déjà pour cette combinaison boutique / catégorie / période.');
        }
    }

    private function getBoutiqueList(): \Illuminate\Support\Collection
    {
        return Boutique::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
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

    private function getYears(): array
    {
        $current = now()->year;
        return range($current - 1, $current + 2);
    }

    private function getAvailableYears(): array
    {
        $current = now()->year;
        return range($current - 2, $current + 2);
    }
}
