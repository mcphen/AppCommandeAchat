<x-mail::message>
# Bonjour {{ $name }} 🔐

Votre mot de passe sur **{{ config('app.name') }}** vient d'être réinitialisé par un administrateur. Voici vos nouveaux identifiants de connexion :

<table width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;background-color:#f8fafc;border:1px solid #e5eaf2;border-radius:12px;">
    <tr>
        <td style="padding:20px 24px;">
            <p style="margin:0 0 4px;font-size:11px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:#94a3b8;">Adresse e-mail</p>
            <p style="margin:0 0 16px;font-size:15px;font-weight:600;color:#1e2a3a;">{{ $email }}</p>
            <p style="margin:0 0 4px;font-size:11px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:#94a3b8;">Nouveau mot de passe</p>
            <p style="margin:0;font-size:15px;font-weight:600;color:#1e2a3a;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;">{{ $plainPassword }}</p>
        </td>
    </tr>
</table>

<x-mail::button :url="route('login')">
Se connecter
</x-mail::button>

<x-slot:subcopy>
🔒 Pour votre sécurité, il vous sera demandé de changer ce mot de passe dès votre prochaine connexion. Si vous n'êtes pas à l'origine de cette demande, contactez votre administrateur immédiatement.
</x-slot:subcopy>

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
