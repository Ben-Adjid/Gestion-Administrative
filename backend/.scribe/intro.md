# Introduction

Données ouvertes du guide indépendant DemarchesTchad : démarches administratives au Tchad (pièces, coûts, délais, lieux), villes et bureaux, parcours de vie, diaspora et calendrier administratif.

<aside>
    <strong>Adresse de base</strong>: <code>http://localhost:8000</code>
</aside>

    Cette API permet à des applications tierces (ONG, applications étudiantes, institutions) de réutiliser
    les fiches de DemarchesTchad. Elle est **en lecture seule** et ne demande **aucune authentification**.

    ## Versions
    Toutes les adresses commencent par `/api/v1/`. Une évolution incompatible donnera lieu à une nouvelle version (`/api/v2/`),
    sans casser les applications existantes.

    ## Langues
    Ajoutez `?lang=en` ou `?lang=ar` pour recevoir les textes traduits (français par défaut ; repli sur le français
    si une traduction manque).

    ## Limite de débit
    - **Sans clé** : 100 requêtes par minute et par adresse IP. Au-delà, l'API répond `429 Too Many Requests` ;
      les en-têtes `X-RateLimit-Limit`, `X-RateLimit-Remaining` et `Retry-After` indiquent où vous en êtes.
    - **Avec une clé partenaire** : aucune limite. Envoyez la clé dans l'en-tête `X-Api-Key: dtk_…`.
      Les clés sont délivrées par l'équipe de DemarchesTchad ; leur utilisation est comptée.

    ## Fiabilité des données
    Chaque fiche indique sa date de dernière vérification (`last_verified_at`) et, quand elle existe, la source de
    l'information. DemarchesTchad est un guide indépendant : vérifiez auprès du service concerné avant une démarche importante.

