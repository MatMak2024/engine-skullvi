<x-mail::message>
# Bonjour {{ $user->displayName() }}

Votre compte est créé. Choisissez un mot de passe pour vous connecter. Ce lien est valable 24 heures.

<x-mail::button :url="$url">
Choisir un mot de passe
</x-mail::button>

Si vous n'êtes pas à l'origine de cette demande, ignorez ce message.
</x-mail::message>
