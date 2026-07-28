<x-mail::message>
# Bonjour {{ $name }},

Un compte vient de vous être créé sur {{ config('app.name') }}.

**Email :** {{ $email }}
**Mot de passe :** {{ $plainPassword }}

<x-mail::button :url="route('login')">
Se connecter
</x-mail::button>

Nous vous recommandons de changer ce mot de passe dès votre première connexion.

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
