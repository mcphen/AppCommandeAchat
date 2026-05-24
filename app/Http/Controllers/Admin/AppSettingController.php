<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AppSettingController extends Controller
{
    public function index(): Response
    {
        $settings = AppSetting::allAsArray();

        return Inertia::render('Admin/AppSettings/Index', [
            'settings'  => $settings,
            'logoUrl'   => isset($settings['company_logo'])
                ? Storage::disk('public')->url($settings['company_logo'])
                : null,
        ]);
    }

    public function updateMail(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mail_mailer'       => ['required', 'in:smtp,sendmail,log'],
            'mail_host'         => ['nullable', 'string', 'max:255'],
            'mail_port'         => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_encryption'   => ['nullable', 'in:tls,ssl,starttls'],
            'mail_username'     => ['nullable', 'string', 'max:255'],
            'mail_password'     => ['nullable', 'string', 'max:255'],
            'mail_from_address' => ['required', 'email', 'max:255'],
            'mail_from_name'    => ['required', 'string', 'max:255'],
        ]);

        // Ne pas écraser le mot de passe si le champ est laissé vide
        if (empty($data['mail_password'])) {
            unset($data['mail_password']);
        }

        AppSetting::setMany($data);

        return back()->with('success', 'Configuration e-mail enregistrée.');
    }

    public function updateCompany(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name'    => ['required', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'company_phone'   => ['nullable', 'string', 'max:50'],
            'company_email'   => ['nullable', 'email', 'max:255'],
            'company_website' => ['nullable', 'url', 'max:255'],
            'company_nif'     => ['nullable', 'string', 'max:100'],
            'company_rccm'    => ['nullable', 'string', 'max:100'],
        ]);

        AppSetting::setMany($data);

        return back()->with('success', 'Identité de l\'entreprise enregistrée.');
    }

    public function updateLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'company_logo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
        ]);

        $oldLogo = AppSetting::get('company_logo');
        if ($oldLogo) {
            Storage::disk('public')->delete($oldLogo);
        }

        $path = $request->file('company_logo')->store('company', 'public');
        AppSetting::set('company_logo', $path);

        return back()->with('success', 'Logo enregistré avec succès.');
    }

    public function deleteLogo(): RedirectResponse
    {
        $logo = AppSetting::get('company_logo');

        if ($logo) {
            Storage::disk('public')->delete($logo);
            AppSetting::set('company_logo', null);
        }

        return back()->with('success', 'Logo supprimé.');
    }

    public function testMail(Request $request): RedirectResponse
    {
        $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        try {
            \Illuminate\Support\Facades\Mail::raw(
                'Ceci est un e-mail de test depuis AchatPro. La configuration SMTP fonctionne correctement.',
                function ($message) use ($request) {
                    $message->to($request->test_email)
                            ->subject('Test SMTP — AchatPro');
                }
            );

            return back()->with('success', "E-mail de test envoyé à {$request->test_email}.");
        } catch (\Exception $e) {
            return back()->with('error', 'Échec de l\'envoi : ' . $e->getMessage());
        }
    }
}
