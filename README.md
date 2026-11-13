# Démarches Tchad — Guide des démarches administratives

Guide citoyen qui explique pour chaque démarche administrative au Tchad : les pièces à fournir, le coût, le délai et le lieu de dépôt.

## Fonctionnalités

- **Plus de 80 démarches** classées par catégorie (passeport, état civil, identité, etc.).
- **Fiches détaillées** : pièces à fournir, coûts, délais, ministère ou bureau responsable.
- **Recherche** avec suggestions et synonymes, et suivi des recherches sans résultat pour améliorer le contenu.
- **Trois langues** : français, anglais et arabe, y compris les fiches et les provinces.
- **Bureaux et géolocalisation** : où déposer chaque dossier.
- **Calendrier** des échéances et **validité des documents**.
- **Espace personnel** : favoris, documents enregistrés et avis sur les démarches.
- **Administration** : gestion du contenu, des statuts de démarche et des clés d'API.

## Technologies

- **Backend** : Laravel (API REST), authentification par jetons et clés d'API.
- **Frontend** : Next.js (React).
- **Base de données** : MySQL.

## Structure

```
backend/    API Laravel et administration
frontend/   Site public Next.js
```

## Installation

### Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

### Frontend

```bash
cd frontend
npm install
```

Créez un fichier `.env.local` :

```bash
NEXT_PUBLIC_API_URL=http://localhost:8000
NEXT_PUBLIC_APP_URL=http://localhost:3000
```

Puis lancez :

```bash
npm run dev
```

## Contenu

Les informations proviennent de sources officielles. Vérifiez toujours les coûts et délais auprès de l'administration avant de vous déplacer : ils peuvent changer.
