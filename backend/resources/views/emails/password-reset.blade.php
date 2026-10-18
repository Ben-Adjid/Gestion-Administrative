@component('mail::message')
# 🔐 Réinitialiser votre mot de passe

Bonjour {{ $user->name }},

Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe.

**Ce lien expire dans {{ $expiresIn }}.**

@component('mail::button', ['url' => $resetUrl])
Réinitialiser le mot de passe
@endcomponent

Ou copiez ce lien dans votre navigateur:
{{ $resetUrl }}

## ⚠️ Important:
- Ce lien est valide une seule fois
- Ne partagez jamais ce lien avec d'autres
- Si vous n'avez pas demandé cette réinitialisation, ignorez cet email

Cordialement,
L'équipe DemarchesTchad

---

*Email envoyé automatiquement - Ne répondez pas à cet email*
@endcomponent
