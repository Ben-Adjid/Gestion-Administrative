@component('mail::message')
# 🎉 Bienvenue sur DemarchesTchad!

Bonjour {{ $user->name }},

Merci de vous être inscrit sur **DemarchesTchad** - le portail officiel des démarches administratives du Tchad.

## Vos informations de connexion:
- **Email:** {{ $user->email }}
@if($password)
- **Mot de passe temporaire:** `{{ $password }}`
@endif

## Prochaines étapes:

1. **Connectez-vous** à votre compte
2. **Explorez les démarches** disponibles
3. **Sauvegardez vos favorites** ❤️
4. **Recevez des notifications** pour les mises à jour

@component('mail::button', ['url' => $loginUrl])
Se connecter maintenant
@endcomponent

## Questions?

Si vous avez besoin d'aide, contactez notre support:
- 📧 **Email:** support@demarches.td
- 💬 **Chat:** Disponible 24/7
- 📞 **Téléphone:** +235 XXX XXX XXX

Cordialement,
L'équipe DemarchesTchad

---

**Note:** Ne partagez jamais votre mot de passe avec quiconque.
@endcomponent
