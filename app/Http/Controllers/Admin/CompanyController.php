<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function index(): Response
    {
        $companies = Company::orderBy('name')->get()->map(function (Company $c) {
            return [
                'id'        => $c->id,
                'name'      => $c->name,
                'code'      => $c->code,
                'address'   => $c->address,
                'phone'     => $c->phone,
                'email'     => $c->email,
                'website'   => $c->website,
                'nif'       => $c->nif,
                'rccm'      => $c->rccm,
                'logo'      => $c->logo,
                'logo_url'  => $c->logo ? Storage::disk('public')->url($c->logo) : null,
                'is_active' => $c->is_active,
            ];
        });

        return Inertia::render('Admin/Companies/Index', [
            'companies' => $companies,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'code'    => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone'   => ['nullable', 'string', 'max:50'],
            'email'   => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'nif'     => ['nullable', 'string', 'max:100'],
            'rccm'    => ['nullable', 'string', 'max:100'],
        ]);

        Company::create($data);

        return back()->with('success', 'Entreprise créée.');
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'code'      => ['nullable', 'string', 'max:20'],
            'address'   => ['nullable', 'string', 'max:500'],
            'phone'     => ['nullable', 'string', 'max:50'],
            'email'     => ['nullable', 'email', 'max:255'],
            'website'   => ['nullable', 'url', 'max:255'],
            'nif'       => ['nullable', 'string', 'max:100'],
            'rccm'      => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $company->update($data);

        return back()->with('success', 'Entreprise mise à jour.');
    }

    public function updateLogo(Request $request, Company $company): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
        ]);

        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);
        }

        $path = $request->file('logo')->store('companies/' . $company->id, 'public');
        $company->update(['logo' => $path]);

        return back()->with('success', 'Logo mis à jour.');
    }

    public function deleteLogo(Company $company): RedirectResponse
    {
        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);
            $company->update(['logo' => null]);
        }

        return back()->with('success', 'Logo supprimé.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        if ($company->disbursementRequests()->exists()) {
            return back()->with('error', 'Impossible de supprimer une entreprise liée à des demandes de décaissement.');
        }

        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);
        }

        $company->delete();

        return back()->with('success', 'Entreprise supprimée.');
    }
}
