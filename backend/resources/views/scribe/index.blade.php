<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>API publique DemarchesTchad</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.style.css") }}" media="screen">
    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.print.css") }}" media="print">

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

    <style id="language-style">
        /* starts out as display none and is replaced with js later  */
                    body .content .bash-example code { display: none; }
                    body .content .javascript-example code { display: none; }
            </style>

    <script>
        var tryItOutBaseUrl = "http://localhost:8000";
        var useCsrf = Boolean();
        var csrfUrl = "/sanctum/csrf-cookie";
    </script>
    <script src="{{ asset("/vendor/scribe/js/tryitout-5.11.0.js") }}"></script>

    <script src="{{ asset("/vendor/scribe/js/theme-default-5.11.0.js") }}"></script>

</head>

<body data-languages="[&quot;bash&quot;,&quot;javascript&quot;]">

<a href="#" id="nav-button">
    <span>
        MENU
        <img src="{{ asset("/vendor/scribe/images/navbar.png") }}" alt="navbar-image"/>
    </span>
</a>
<div class="tocify-wrapper">
    
            <div class="lang-selector">
                                            <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                            <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                    </div>
    
    <div class="search">
        <input type="text" class="search" id="input-search" placeholder="Rechercher">
    </div>

    <div id="toc">
                    <ul id="tocify-header-introduction" class="tocify-header">
                <li class="tocify-item level-1" data-unique="introduction">
                    <a href="#introduction">Introduction</a>
                </li>
                            </ul>
                    <ul id="tocify-header-authentification" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authentification">
                    <a href="#authentification">Authentification</a>
                </li>
                            </ul>
                    <ul id="tocify-header-demarches" class="tocify-header">
                <li class="tocify-item level-1" data-unique="demarches">
                    <a href="#demarches">Démarches</a>
                </li>
                                    <ul id="tocify-subheader-demarches" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="demarches-GETapi-v1-demarches">
                                <a href="#demarches-GETapi-v1-demarches">Lister les démarches</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="demarches-GETapi-v1-demarches--slug-">
                                <a href="#demarches-GETapi-v1-demarches--slug-">Consulter une démarche</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-categories" class="tocify-header">
                <li class="tocify-item level-1" data-unique="categories">
                    <a href="#categories">Catégories</a>
                </li>
                                    <ul id="tocify-subheader-categories" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="categories-GETapi-v1-categories">
                                <a href="#categories-GETapi-v1-categories">Lister les catégories</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="categories-GETapi-v1-categories--slug--demarches">
                                <a href="#categories-GETapi-v1-categories--slug--demarches">Démarches d’une catégorie</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-recherche" class="tocify-header">
                <li class="tocify-item level-1" data-unique="recherche">
                    <a href="#recherche">Recherche</a>
                </li>
                                    <ul id="tocify-subheader-recherche" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="recherche-GETapi-v1-search-suggest">
                                <a href="#recherche-GETapi-v1-search-suggest">Suggestions de recherche</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-villes-et-bureaux" class="tocify-header">
                <li class="tocify-item level-1" data-unique="villes-et-bureaux">
                    <a href="#villes-et-bureaux">Villes et bureaux</a>
                </li>
                                    <ul id="tocify-subheader-villes-et-bureaux" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="villes-et-bureaux-GETapi-v1-cities">
                                <a href="#villes-et-bureaux-GETapi-v1-cities">Lister les villes</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="villes-et-bureaux-GETapi-v1-cities--slug-">
                                <a href="#villes-et-bureaux-GETapi-v1-cities--slug-">Consulter une ville</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-parcours-de-vie" class="tocify-header">
                <li class="tocify-item level-1" data-unique="parcours-de-vie">
                    <a href="#parcours-de-vie">Parcours de vie</a>
                </li>
                                    <ul id="tocify-subheader-parcours-de-vie" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="parcours-de-vie-GETapi-v1-life-events">
                                <a href="#parcours-de-vie-GETapi-v1-life-events">Lister les parcours de vie</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="parcours-de-vie-GETapi-v1-life-events--slug-">
                                <a href="#parcours-de-vie-GETapi-v1-life-events--slug-">Consulter un parcours de vie</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-diaspora" class="tocify-header">
                <li class="tocify-item level-1" data-unique="diaspora">
                    <a href="#diaspora">Diaspora</a>
                </li>
                                    <ul id="tocify-subheader-diaspora" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="diaspora-GETapi-v1-diaspora">
                                <a href="#diaspora-GETapi-v1-diaspora">Diaspora</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-calendrier" class="tocify-header">
                <li class="tocify-item level-1" data-unique="calendrier">
                    <a href="#calendrier">Calendrier</a>
                </li>
                                    <ul id="tocify-subheader-calendrier" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="calendrier-GETapi-v1-calendar">
                                <a href="#calendrier-GETapi-v1-calendar">Calendrier administratif</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="calendrier-GETapi-v1-calendar--id--ics">
                                <a href="#calendrier-GETapi-v1-calendar--id--ics">Ajouter une date à son agenda</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-communaute" class="tocify-header">
                <li class="tocify-item level-1" data-unique="communaute">
                    <a href="#communaute">Communauté</a>
                </li>
                                    <ul id="tocify-subheader-communaute" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="communaute-GETapi-v1-demarches--slug--reviews">
                                <a href="#communaute-GETapi-v1-demarches--slug--reviews">Avis sur une démarche</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="communaute-GETapi-v1-questions">
                                <a href="#communaute-GETapi-v1-questions">Questions et réponses</a>
                            </li>
                                                                        </ul>
                            </ul>
            </div>

    <ul class="toc-footer" id="toc-footer">
                    <li style="padding-bottom: 5px;"><a href="{{ route("scribe.postman") }}">Collection Postman</a></li>
                            <li style="padding-bottom: 5px;"><a href="{{ route("scribe.openapi") }}">Spécification OpenAPI</a></li>
                <li><a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a></li>
    </ul>

    <ul class="toc-footer" id="last-updated">
        <li>Mise à jour : 15/10/2026</li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        <h1 id="introduction">Introduction</h1>
<p>Données ouvertes du guide indépendant DemarchesTchad : démarches administratives au Tchad (pièces, coûts, délais, lieux), villes et bureaux, parcours de vie, diaspora et calendrier administratif.</p>
<aside>
    <strong>Adresse de base</strong>: <code>http://localhost:8000</code>
</aside>
<pre><code>Cette API permet à des applications tierces (ONG, applications étudiantes, institutions) de réutiliser
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
l'information. DemarchesTchad est un guide indépendant : vérifiez auprès du service concerné avant une démarche importante.</code></pre>

        <h1 id="authentification">Authentification</h1>
<p>Aucune authentification n’est nécessaire. Une clé partenaire facultative, envoyée dans l’en-tête <strong><code>X-Api-Key</code></strong>, supprime la limite de 100 requêtes par minute.</p>

        <h1 id="demarches">Démarches</h1>

    

                                <h2 id="demarches-GETapi-v1-demarches">Lister les démarches</h2>

<p>
</p>

<p>Liste paginée des fiches publiées, avec recherche (synonymes compris), filtres et tri.</p>

<span id="example-requests-GETapi-v1-demarches">
<blockquote>Exemple de requête:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/demarches?q=passeport&amp;categorie=identite&amp;is_online=&amp;sort=views&amp;per_page=15&amp;page=1&amp;lang=fr" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/demarches"
);

const params = {
    "q": "passeport",
    "categorie": "identite",
    "is_online": "0",
    "sort": "views",
    "per_page": "15",
    "page": "1",
    "lang": "fr",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-demarches">
            <blockquote>
            <p>Exemple de réponse (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
content-security-policy: default-src &#039;none&#039;; frame-ancestors &#039;none&#039;; base-uri &#039;none&#039;; form-action &#039;none&#039;
x-content-type-options: nosniff
x-frame-options: DENY
referrer-policy: strict-origin-when-cross-origin
permissions-policy: geolocation=(), microphone=(), camera=()
cross-origin-resource-policy: same-site
x-ratelimit-limit: 100
x-ratelimit-remaining: 99
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [],
    &quot;links&quot;: {
        &quot;first&quot;: &quot;http://localhost:8000/api/v1/demarches?page=1&quot;,
        &quot;last&quot;: &quot;http://localhost:8000/api/v1/demarches?page=1&quot;,
        &quot;prev&quot;: null,
        &quot;next&quot;: null
    },
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;from&quot;: null,
        &quot;last_page&quot;: 1,
        &quot;links&quot;: [
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;&amp;laquo; Previous&quot;,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;http://localhost:8000/api/v1/demarches?page=1&quot;,
                &quot;label&quot;: &quot;1&quot;,
                &quot;active&quot;: true
            },
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;Next &amp;raquo;&quot;,
                &quot;active&quot;: false
            }
        ],
        &quot;path&quot;: &quot;http://localhost:8000/api/v1/demarches&quot;,
        &quot;per_page&quot;: 15,
        &quot;to&quot;: null,
        &quot;total&quot;: 0
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-demarches" hidden>
    <blockquote>Réponse reçue<span
                id="execution-response-status-GETapi-v1-demarches"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-demarches"
      data-empty-response-text="<Réponse vide>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-demarches" hidden>
    <blockquote>La requête a échoué:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-demarches">

Vérifiez votre connexion. Les détails de l’erreur sont dans la console du navigateur.</code></pre>
</span>
<form id="form-GETapi-v1-demarches" data-method="GET"
      data-path="api/v1/demarches"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-demarches', this);">
    <h3>
        Requête&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-demarches"
                    onclick="tryItOut('GETapi-v1-demarches');">Essayer
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-demarches"
                    onclick="cancelTryOut('GETapi-v1-demarches');" hidden>Annuler
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-demarches"
                    data-initial-text="Envoyer la requête"
                    data-loading-text="Envoi…"
                    hidden>Envoyer la requête
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/demarches</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>En-têtes</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-demarches"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-demarches"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Paramètres de requête</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>q</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="q"                data-endpoint="GETapi-v1-demarches"
               value="passeport"
               data-component="query">
    <br>
<p>Mot-clé recherché dans le titre, le résumé, la description, l’institution et le lieu (alias : search). Example: <code>passeport</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>categorie</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="categorie"                data-endpoint="GETapi-v1-demarches"
               value="identite"
               data-component="query">
    <br>
<p>Slug ou identifiant de catégorie (alias : category). Example: <code>identite</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>is_online</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="GETapi-v1-demarches" style="display: none">
            <input type="radio" name="is_online"
                   value="1"
                   data-endpoint="GETapi-v1-demarches"
                   data-component="query"             >
            <code>true</code>
        </label>
        <label data-endpoint="GETapi-v1-demarches" style="display: none">
            <input type="radio" name="is_online"
                   value="0"
                   data-endpoint="GETapi-v1-demarches"
                   data-component="query"             >
            <code>false</code>
        </label>
    <br>
<p>Seulement les démarches faisables en ligne (true) ou sur place (false). Example: <code>false</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>sort</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="sort"                data-endpoint="GETapi-v1-demarches"
               value="views"
               data-component="query">
    <br>
<p>Tri : relevance (par défaut avec q), views (par défaut sans q), title, recent. Example: <code>views</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-demarches"
               value="15"
               data-component="query">
    <br>
<p>Fiches par page, de 1 à 50 (15 par défaut). Example: <code>15</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-demarches"
               value="1"
               data-component="query">
    <br>
<p>Numéro de page. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>lang</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lang"                data-endpoint="GETapi-v1-demarches"
               value="fr"
               data-component="query">
    <br>
<p>Langue du contenu : fr (par défaut), en ou ar. Example: <code>fr</code></p>
            </div>
                </form>

                    <h2 id="demarches-GETapi-v1-demarches--slug-">Consulter une démarche</h2>

<p>
</p>

<p>Fiche complète : pièces, conditions, étapes, coût, délai, institution et bureaux où l’effectuer (avec leur ville).</p>

<span id="example-requests-GETapi-v1-demarches--slug-">
<blockquote>Exemple de requête:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/demarches/passeport-biometrique?lang=fr" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/demarches/passeport-biometrique"
);

const params = {
    "lang": "fr",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-demarches--slug-">
            <blockquote>
            <p>Exemple de réponse (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
content-security-policy: default-src &#039;none&#039;; frame-ancestors &#039;none&#039;; base-uri &#039;none&#039;; form-action &#039;none&#039;
x-content-type-options: nosniff
x-frame-options: DENY
referrer-policy: strict-origin-when-cross-origin
permissions-policy: geolocation=(), microphone=(), camera=()
cross-origin-resource-policy: same-site
x-ratelimit-limit: 100
x-ratelimit-remaining: 99
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: 8,
        &quot;language&quot;: &quot;fr&quot;,
        &quot;is_translated&quot;: true,
        &quot;title&quot;: &quot;Passeport Biom&eacute;trique&quot;,
        &quot;slug&quot;: &quot;passeport-biometrique&quot;,
        &quot;summary&quot;: &quot;Obtenir ou renouveler votre passeport biom&eacute;trique tchadien&quot;,
        &quot;description&quot;: &quot;Le passeport biom&eacute;trique est le document de voyage officiel de la R&eacute;publique du Tchad. Il int&egrave;gre une puce &eacute;lectronique contenant les donn&eacute;es biom&eacute;triques du titulaire (empreintes, photo). Obligatoire pour tout voyage international.&quot;,
        &quot;required_documents&quot;: [
            &quot;Acte de naissance (original ou copie l&eacute;galis&eacute;e)&quot;,
            &quot;Carte d&#039;identit&eacute; nationale valide&quot;,
            &quot;Deux photos biom&eacute;triques r&eacute;centes (fond blanc, 4x6)&quot;,
            &quot;Extrait de casier judiciaire vierge (moins de 3 mois)&quot;,
            &quot;Re&ccedil;u de paiement des frais&quot;,
            &quot;Ancien passeport (si renouvellement)&quot;
        ],
        &quot;procedure_steps&quot;: [
            &quot;Retirer le formulaire de demande au bureau des passeports&quot;,
            &quot;Remplir et signer le formulaire&quot;,
            &quot;Constituer le dossier complet&quot;,
            &quot;D&eacute;poser le dossier au guichet&quot;,
            &quot;Payer les frais de 75 000 FCFA &agrave; la caisse&quot;,
            &quot;Se pr&eacute;senter pour la prise des empreintes biom&eacute;triques&quot;,
            &quot;Attendre la notification (SMS ou affichage)&quot;,
            &quot;R&eacute;cup&eacute;rer le passeport muni de votre re&ccedil;u&quot;
        ],
        &quot;conditions&quot;: [],
        &quot;cost&quot;: &quot;60 000 FCFA&quot;,
        &quot;cost_amount&quot;: 60000,
        &quot;extra_costs&quot;: [],
        &quot;cost_estimate&quot;: {
            &quot;official&quot;: 60000,
            &quot;extras&quot;: 0,
            &quot;total&quot;: 60000,
            &quot;complete&quot;: true
        },
        &quot;abroad_available&quot;: false,
        &quot;abroad_info&quot;: null,
        &quot;offices&quot;: [
            {
                &quot;id&quot;: 19,
                &quot;name&quot;: &quot;Antenne ANATS de Mongo&quot;,
                &quot;address&quot;: null,
                &quot;opening_hours&quot;: null,
                &quot;phone&quot;: null,
                &quot;latitude&quot;: null,
                &quot;longitude&quot;: null,
                &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Antenne%20ANATS%20de%20Mongo%2C%20Mongo%2C%20Tchad&quot;,
                &quot;source_url&quot;: &quot;https://www.alwihdainfo.com/tchad-afflux-des-voyageurs-a-l-anats-de-mongo-pour-la-delivrance-du-passeport-a104358/&quot;,
                &quot;is_active&quot;: true,
                &quot;city&quot;: {
                    &quot;id&quot;: 19,
                    &quot;slug&quot;: &quot;mongo&quot;,
                    &quot;name&quot;: &quot;Mongo&quot;
                }
            },
            {
                &quot;id&quot;: 17,
                &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 10e arrondissement&quot;,
                &quot;address&quot;: null,
                &quot;opening_hours&quot;: null,
                &quot;phone&quot;: null,
                &quot;latitude&quot;: null,
                &quot;longitude&quot;: null,
                &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%2010e%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                &quot;is_active&quot;: true,
                &quot;city&quot;: {
                    &quot;id&quot;: 1,
                    &quot;slug&quot;: &quot;ndjamena&quot;,
                    &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                }
            },
            {
                &quot;id&quot;: 11,
                &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 1er arrondissement&quot;,
                &quot;address&quot;: null,
                &quot;opening_hours&quot;: null,
                &quot;phone&quot;: null,
                &quot;latitude&quot;: null,
                &quot;longitude&quot;: null,
                &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%201er%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                &quot;is_active&quot;: true,
                &quot;city&quot;: {
                    &quot;id&quot;: 1,
                    &quot;slug&quot;: &quot;ndjamena&quot;,
                    &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                }
            },
            {
                &quot;id&quot;: 12,
                &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 2e arrondissement&quot;,
                &quot;address&quot;: null,
                &quot;opening_hours&quot;: null,
                &quot;phone&quot;: null,
                &quot;latitude&quot;: null,
                &quot;longitude&quot;: null,
                &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%202e%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                &quot;is_active&quot;: true,
                &quot;city&quot;: {
                    &quot;id&quot;: 1,
                    &quot;slug&quot;: &quot;ndjamena&quot;,
                    &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                }
            },
            {
                &quot;id&quot;: 13,
                &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 5e arrondissement&quot;,
                &quot;address&quot;: null,
                &quot;opening_hours&quot;: null,
                &quot;phone&quot;: null,
                &quot;latitude&quot;: null,
                &quot;longitude&quot;: null,
                &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%205e%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                &quot;is_active&quot;: true,
                &quot;city&quot;: {
                    &quot;id&quot;: 1,
                    &quot;slug&quot;: &quot;ndjamena&quot;,
                    &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                }
            },
            {
                &quot;id&quot;: 14,
                &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 6e arrondissement&quot;,
                &quot;address&quot;: null,
                &quot;opening_hours&quot;: null,
                &quot;phone&quot;: null,
                &quot;latitude&quot;: null,
                &quot;longitude&quot;: null,
                &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%206e%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                &quot;is_active&quot;: true,
                &quot;city&quot;: {
                    &quot;id&quot;: 1,
                    &quot;slug&quot;: &quot;ndjamena&quot;,
                    &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                }
            },
            {
                &quot;id&quot;: 15,
                &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 7e arrondissement&quot;,
                &quot;address&quot;: null,
                &quot;opening_hours&quot;: null,
                &quot;phone&quot;: null,
                &quot;latitude&quot;: null,
                &quot;longitude&quot;: null,
                &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%207e%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                &quot;is_active&quot;: true,
                &quot;city&quot;: {
                    &quot;id&quot;: 1,
                    &quot;slug&quot;: &quot;ndjamena&quot;,
                    &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                }
            },
            {
                &quot;id&quot;: 16,
                &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 8e arrondissement&quot;,
                &quot;address&quot;: null,
                &quot;opening_hours&quot;: null,
                &quot;phone&quot;: null,
                &quot;latitude&quot;: null,
                &quot;longitude&quot;: null,
                &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%208e%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                &quot;is_active&quot;: true,
                &quot;city&quot;: {
                    &quot;id&quot;: 1,
                    &quot;slug&quot;: &quot;ndjamena&quot;,
                    &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                }
            },
            {
                &quot;id&quot;: 18,
                &quot;name&quot;: &quot;Centre de d&eacute;livrance de titres s&eacute;curis&eacute;s de Bongor (ANATS)&quot;,
                &quot;address&quot;: null,
                &quot;opening_hours&quot;: null,
                &quot;phone&quot;: null,
                &quot;latitude&quot;: null,
                &quot;longitude&quot;: null,
                &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20de%20d%C3%A9livrance%20de%20titres%20s%C3%A9curis%C3%A9s%20de%20Bongor%20%28ANATS%29%2C%20Bongor%2C%20Tchad&quot;,
                &quot;source_url&quot;: &quot;https://www.alwihdainfo.com/tchad-ouverture-d-un-centre-de-delivrance-de-titres-securises-a-bongor-a120987/&quot;,
                &quot;is_active&quot;: true,
                &quot;city&quot;: {
                    &quot;id&quot;: 9,
                    &quot;slug&quot;: &quot;bongor&quot;,
                    &quot;name&quot;: &quot;Bongor&quot;
                }
            }
        ],
        &quot;processing_time&quot;: &quot;15-30 jours&quot;,
        &quot;processing_time_days&quot;: null,
        &quot;institution&quot;: &quot;Agence Nationale des Titres S&eacute;curis&eacute;s (ANATS)&quot;,
        &quot;location&quot;: &quot;Centres d&#039;accueil de l&#039;ANATS (N&#039;Djam&eacute;na et chefs-lieux de province)&quot;,
        &quot;contact_email&quot;: null,
        &quot;contact_phone&quot;: null,
        &quot;official_source_url&quot;: &quot;https://www.alwihdainfo.com/tchad-ouverture-d-un-centre-de-delivrance-de-titres-securises-a-bongor-a120987/&quot;,
        &quot;last_verified_at&quot;: null,
        &quot;status&quot;: &quot;published&quot;,
        &quot;is_online&quot;: false,
        &quot;online_url&quot;: null,
        &quot;views_count&quot;: 2,
        &quot;categorie&quot;: {
            &quot;id&quot;: 14,
            &quot;name&quot;: &quot;Voyage et Visa&quot;,
            &quot;name_fr&quot;: &quot;Voyage et Visa&quot;,
            &quot;slug&quot;: &quot;voyage&quot;,
            &quot;description&quot;: &quot;Passeport, visas et documents de voyage.&quot;,
            &quot;icon&quot;: &quot;ti-plane&quot;,
            &quot;color&quot;: &quot;gray&quot;,
            &quot;display_order&quot;: 8,
            &quot;is_active&quot;: true
        },
        &quot;created_at&quot;: &quot;2026-10-11T12:48:11.000000Z&quot;,
        &quot;updated_at&quot;: &quot;2026-10-14T17:17:50.000000Z&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-demarches--slug-" hidden>
    <blockquote>Réponse reçue<span
                id="execution-response-status-GETapi-v1-demarches--slug-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-demarches--slug-"
      data-empty-response-text="<Réponse vide>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-demarches--slug-" hidden>
    <blockquote>La requête a échoué:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-demarches--slug-">

Vérifiez votre connexion. Les détails de l’erreur sont dans la console du navigateur.</code></pre>
</span>
<form id="form-GETapi-v1-demarches--slug-" data-method="GET"
      data-path="api/v1/demarches/{slug}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-demarches--slug-', this);">
    <h3>
        Requête&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-demarches--slug-"
                    onclick="tryItOut('GETapi-v1-demarches--slug-');">Essayer
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-demarches--slug-"
                    onclick="cancelTryOut('GETapi-v1-demarches--slug-');" hidden>Annuler
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-demarches--slug-"
                    data-initial-text="Envoyer la requête"
                    data-loading-text="Envoi…"
                    hidden>Envoyer la requête
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/demarches/{slug}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>En-têtes</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-demarches--slug-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-demarches--slug-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Paramètres d’adresse</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>slug</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="slug"                data-endpoint="GETapi-v1-demarches--slug-"
               value="passeport-biometrique"
               data-component="url">
    <br>
<p>Identifiant de la fiche. Example: <code>passeport-biometrique</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Paramètres de requête</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>lang</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lang"                data-endpoint="GETapi-v1-demarches--slug-"
               value="fr"
               data-component="query">
    <br>
<p>Langue du contenu : fr (par défaut), en ou ar. Example: <code>fr</code></p>
            </div>
                </form>

                <h1 id="categories">Catégories</h1>

    

                                <h2 id="categories-GETapi-v1-categories">Lister les catégories</h2>

<p>
</p>

<p>Catégories actives avec le nombre de fiches publiées de chacune.</p>

<span id="example-requests-GETapi-v1-categories">
<blockquote>Exemple de requête:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/categories?lang=fr" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/categories"
);

const params = {
    "lang": "fr",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-categories">
            <blockquote>
            <p>Exemple de réponse (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
content-security-policy: default-src &#039;none&#039;; frame-ancestors &#039;none&#039;; base-uri &#039;none&#039;; form-action &#039;none&#039;
x-content-type-options: nosniff
x-frame-options: DENY
referrer-policy: strict-origin-when-cross-origin
permissions-policy: geolocation=(), microphone=(), camera=()
cross-origin-resource-policy: same-site
x-ratelimit-limit: 100
x-ratelimit-remaining: 99
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: 12,
            &quot;name&quot;: &quot;&Eacute;tat civil&quot;,
            &quot;name_fr&quot;: &quot;&Eacute;tat civil&quot;,
            &quot;slug&quot;: &quot;etat-civil&quot;,
            &quot;description&quot;: &quot;Actes de naissance, de mariage et de d&eacute;c&egrave;s.&quot;,
            &quot;icon&quot;: &quot;ti-heart&quot;,
            &quot;color&quot;: &quot;teal&quot;,
            &quot;display_order&quot;: 1,
            &quot;is_active&quot;: true,
            &quot;demarches_count&quot;: 3
        },
        {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;&Eacute;tudes&quot;,
            &quot;name_fr&quot;: &quot;&Eacute;tudes&quot;,
            &quot;slug&quot;: &quot;etudes&quot;,
            &quot;description&quot;: &quot;Proc&eacute;dures d&#039;&eacute;ducation&quot;,
            &quot;icon&quot;: &quot;ti-school&quot;,
            &quot;color&quot;: &quot;#2563EB&quot;,
            &quot;display_order&quot;: 2,
            &quot;is_active&quot;: true,
            &quot;demarches_count&quot;: 1
        },
        {
            &quot;id&quot;: 11,
            &quot;name&quot;: &quot;Identit&eacute;&quot;,
            &quot;name_fr&quot;: &quot;Identit&eacute;&quot;,
            &quot;slug&quot;: &quot;identite&quot;,
            &quot;description&quot;: &quot;Carte d&rsquo;identit&eacute;, inscription &eacute;lectorale et documents d&rsquo;identit&eacute;.&quot;,
            &quot;icon&quot;: &quot;ti-id&quot;,
            &quot;color&quot;: &quot;blue&quot;,
            &quot;display_order&quot;: 3,
            &quot;is_active&quot;: true,
            &quot;demarches_count&quot;: 2
        },
        {
            &quot;id&quot;: 13,
            &quot;name&quot;: &quot;L&eacute;galisations&quot;,
            &quot;name_fr&quot;: &quot;L&eacute;galisations&quot;,
            &quot;slug&quot;: &quot;legalisations&quot;,
            &quot;description&quot;: &quot;L&eacute;galisation de signatures et certification de copies.&quot;,
            &quot;icon&quot;: &quot;ti-certificate&quot;,
            &quot;color&quot;: &quot;#2563EB&quot;,
            &quot;display_order&quot;: 4,
            &quot;is_active&quot;: true,
            &quot;demarches_count&quot;: 0
        },
        {
            &quot;id&quot;: 2,
            &quot;name&quot;: &quot;Sant&eacute;&quot;,
            &quot;name_fr&quot;: &quot;Sant&eacute;&quot;,
            &quot;slug&quot;: &quot;sante&quot;,
            &quot;description&quot;: &quot;Services de sant&eacute;&quot;,
            &quot;icon&quot;: &quot;ti-heart-rate-monitor&quot;,
            &quot;color&quot;: &quot;coral&quot;,
            &quot;display_order&quot;: 5,
            &quot;is_active&quot;: true,
            &quot;demarches_count&quot;: 1
        },
        {
            &quot;id&quot;: 6,
            &quot;name&quot;: &quot;Travail&quot;,
            &quot;name_fr&quot;: &quot;Travail&quot;,
            &quot;slug&quot;: &quot;travail&quot;,
            &quot;description&quot;: &quot;Emploi et assurance&quot;,
            &quot;icon&quot;: &quot;ti-briefcase&quot;,
            &quot;color&quot;: &quot;green&quot;,
            &quot;display_order&quot;: 6,
            &quot;is_active&quot;: true,
            &quot;demarches_count&quot;: 0
        },
        {
            &quot;id&quot;: 3,
            &quot;name&quot;: &quot;Justice&quot;,
            &quot;name_fr&quot;: &quot;Justice&quot;,
            &quot;slug&quot;: &quot;justice&quot;,
            &quot;description&quot;: &quot;Proc&eacute;dures judiciaires&quot;,
            &quot;icon&quot;: &quot;ti-gavel&quot;,
            &quot;color&quot;: &quot;pink&quot;,
            &quot;display_order&quot;: 7,
            &quot;is_active&quot;: true,
            &quot;demarches_count&quot;: 1
        },
        {
            &quot;id&quot;: 14,
            &quot;name&quot;: &quot;Voyage et Visa&quot;,
            &quot;name_fr&quot;: &quot;Voyage et Visa&quot;,
            &quot;slug&quot;: &quot;voyage&quot;,
            &quot;description&quot;: &quot;Passeport, visas et documents de voyage.&quot;,
            &quot;icon&quot;: &quot;ti-plane&quot;,
            &quot;color&quot;: &quot;gray&quot;,
            &quot;display_order&quot;: 8,
            &quot;is_active&quot;: true,
            &quot;demarches_count&quot;: 1
        },
        {
            &quot;id&quot;: 5,
            &quot;name&quot;: &quot;Transport&quot;,
            &quot;name_fr&quot;: &quot;Transport&quot;,
            &quot;slug&quot;: &quot;transport&quot;,
            &quot;description&quot;: &quot;Permis et documents&quot;,
            &quot;icon&quot;: &quot;ti-car&quot;,
            &quot;color&quot;: &quot;red&quot;,
            &quot;display_order&quot;: 9,
            &quot;is_active&quot;: true,
            &quot;demarches_count&quot;: 1
        },
        {
            &quot;id&quot;: 4,
            &quot;name&quot;: &quot;Urbanisme et logement&quot;,
            &quot;name_fr&quot;: &quot;Urbanisme et logement&quot;,
            &quot;slug&quot;: &quot;urbanisme&quot;,
            &quot;description&quot;: &quot;Permis de construire, terrains et logement.&quot;,
            &quot;icon&quot;: &quot;ti-building&quot;,
            &quot;color&quot;: &quot;orange&quot;,
            &quot;display_order&quot;: 10,
            &quot;is_active&quot;: true,
            &quot;demarches_count&quot;: 1
        }
    ]
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-categories" hidden>
    <blockquote>Réponse reçue<span
                id="execution-response-status-GETapi-v1-categories"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-categories"
      data-empty-response-text="<Réponse vide>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-categories" hidden>
    <blockquote>La requête a échoué:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-categories">

Vérifiez votre connexion. Les détails de l’erreur sont dans la console du navigateur.</code></pre>
</span>
<form id="form-GETapi-v1-categories" data-method="GET"
      data-path="api/v1/categories"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-categories', this);">
    <h3>
        Requête&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-categories"
                    onclick="tryItOut('GETapi-v1-categories');">Essayer
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-categories"
                    onclick="cancelTryOut('GETapi-v1-categories');" hidden>Annuler
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-categories"
                    data-initial-text="Envoyer la requête"
                    data-loading-text="Envoi…"
                    hidden>Envoyer la requête
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/categories</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>En-têtes</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-categories"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-categories"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Paramètres de requête</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>lang</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lang"                data-endpoint="GETapi-v1-categories"
               value="fr"
               data-component="query">
    <br>
<p>Langue des noms : fr (par défaut), en ou ar. Example: <code>fr</code></p>
            </div>
                </form>

                    <h2 id="categories-GETapi-v1-categories--slug--demarches">Démarches d’une catégorie</h2>

<p>
</p>

<p>Fiches publiées d’une catégorie, les plus consultées d’abord ; la catégorie est jointe à la réponse.</p>

<span id="example-requests-GETapi-v1-categories--slug--demarches">
<blockquote>Exemple de requête:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/categories/etudes/demarches?per_page=50&amp;lang=fr" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/categories/etudes/demarches"
);

const params = {
    "per_page": "50",
    "lang": "fr",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-categories--slug--demarches">
            <blockquote>
            <p>Exemple de réponse (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
content-security-policy: default-src &#039;none&#039;; frame-ancestors &#039;none&#039;; base-uri &#039;none&#039;; form-action &#039;none&#039;
x-content-type-options: nosniff
x-frame-options: DENY
referrer-policy: strict-origin-when-cross-origin
permissions-policy: geolocation=(), microphone=(), camera=()
cross-origin-resource-policy: same-site
x-ratelimit-limit: 100
x-ratelimit-remaining: 99
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: 5,
            &quot;language&quot;: &quot;fr&quot;,
            &quot;is_translated&quot;: true,
            &quot;title&quot;: &quot;Inscription Universit&eacute;&quot;,
            &quot;slug&quot;: &quot;inscription-universite&quot;,
            &quot;summary&quot;: &quot;S&#039;inscrire dans une universit&eacute; tchadienne&quot;,
            &quot;description&quot;: &quot;L&#039;inscription universitaire est le processus permettant aux &eacute;tudiants admis d&#039;enregistrer leur admission et de payer les frais d&#039;inscription. Chaque universit&eacute; a ses propres frais et conditions.&quot;,
            &quot;required_documents&quot;: [
                &quot;Lettre d&#039;admission&quot;,
                &quot;Relev&eacute; de notes BAC&quot;,
                &quot;Copie identit&eacute;&quot;,
                &quot;Certificat de naissance&quot;,
                &quot;Photo 4x6&quot;
            ],
            &quot;procedure_steps&quot;: [
                &quot;Recevoir lettre d&#039;admission&quot;,
                &quot;Retirer formulaire&quot;,
                &quot;Payer les frais&quot;,
                &quot;D&eacute;poser dossier&quot;,
                &quot;Attendre confirmation&quot;,
                &quot;Recevoir num&eacute;ro inscription&quot;
            ],
            &quot;conditions&quot;: [],
            &quot;cost&quot;: &quot;50&nbsp;000 FCFA&quot;,
            &quot;cost_amount&quot;: 50000,
            &quot;extra_costs&quot;: [],
            &quot;cost_estimate&quot;: {
                &quot;official&quot;: 50000,
                &quot;extras&quot;: 0,
                &quot;total&quot;: 50000,
                &quot;complete&quot;: true
            },
            &quot;abroad_available&quot;: false,
            &quot;abroad_info&quot;: null,
            &quot;processing_time&quot;: &quot;3-5 jours&quot;,
            &quot;processing_time_days&quot;: null,
            &quot;institution&quot;: &quot;Universit&eacute; de N&#039;Djam&eacute;na&quot;,
            &quot;location&quot;: &quot;Campus Principal, Quartier du Fleuve&quot;,
            &quot;contact_email&quot;: null,
            &quot;contact_phone&quot;: null,
            &quot;official_source_url&quot;: null,
            &quot;last_verified_at&quot;: null,
            &quot;status&quot;: &quot;published&quot;,
            &quot;is_online&quot;: false,
            &quot;online_url&quot;: null,
            &quot;views_count&quot;: 0,
            &quot;categorie&quot;: {
                &quot;id&quot;: 1,
                &quot;name&quot;: &quot;&Eacute;tudes&quot;,
                &quot;name_fr&quot;: &quot;&Eacute;tudes&quot;,
                &quot;slug&quot;: &quot;etudes&quot;,
                &quot;description&quot;: &quot;Proc&eacute;dures d&#039;&eacute;ducation&quot;,
                &quot;icon&quot;: &quot;ti-school&quot;,
                &quot;color&quot;: &quot;#2563EB&quot;,
                &quot;display_order&quot;: 2,
                &quot;is_active&quot;: true
            },
            &quot;created_at&quot;: &quot;2026-10-11T12:05:57.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2026-10-14T17:17:50.000000Z&quot;
        }
    ],
    &quot;links&quot;: {
        &quot;first&quot;: &quot;http://localhost:8000/api/v1/categories/etudes/demarches?page=1&quot;,
        &quot;last&quot;: &quot;http://localhost:8000/api/v1/categories/etudes/demarches?page=1&quot;,
        &quot;prev&quot;: null,
        &quot;next&quot;: null
    },
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;from&quot;: 1,
        &quot;last_page&quot;: 1,
        &quot;links&quot;: [
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;&amp;laquo; Previous&quot;,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;http://localhost:8000/api/v1/categories/etudes/demarches?page=1&quot;,
                &quot;label&quot;: &quot;1&quot;,
                &quot;active&quot;: true
            },
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;Next &amp;raquo;&quot;,
                &quot;active&quot;: false
            }
        ],
        &quot;path&quot;: &quot;http://localhost:8000/api/v1/categories/etudes/demarches&quot;,
        &quot;per_page&quot;: 50,
        &quot;to&quot;: 1,
        &quot;total&quot;: 1
    },
    &quot;categorie&quot;: {
        &quot;id&quot;: 1,
        &quot;name&quot;: &quot;&Eacute;tudes&quot;,
        &quot;name_fr&quot;: &quot;&Eacute;tudes&quot;,
        &quot;slug&quot;: &quot;etudes&quot;,
        &quot;description&quot;: &quot;Proc&eacute;dures d&#039;&eacute;ducation&quot;,
        &quot;icon&quot;: &quot;ti-school&quot;,
        &quot;color&quot;: &quot;#2563EB&quot;,
        &quot;display_order&quot;: 2,
        &quot;is_active&quot;: true
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-categories--slug--demarches" hidden>
    <blockquote>Réponse reçue<span
                id="execution-response-status-GETapi-v1-categories--slug--demarches"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-categories--slug--demarches"
      data-empty-response-text="<Réponse vide>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-categories--slug--demarches" hidden>
    <blockquote>La requête a échoué:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-categories--slug--demarches">

Vérifiez votre connexion. Les détails de l’erreur sont dans la console du navigateur.</code></pre>
</span>
<form id="form-GETapi-v1-categories--slug--demarches" data-method="GET"
      data-path="api/v1/categories/{slug}/demarches"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-categories--slug--demarches', this);">
    <h3>
        Requête&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-categories--slug--demarches"
                    onclick="tryItOut('GETapi-v1-categories--slug--demarches');">Essayer
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-categories--slug--demarches"
                    onclick="cancelTryOut('GETapi-v1-categories--slug--demarches');" hidden>Annuler
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-categories--slug--demarches"
                    data-initial-text="Envoyer la requête"
                    data-loading-text="Envoi…"
                    hidden>Envoyer la requête
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/categories/{slug}/demarches</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>En-têtes</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-categories--slug--demarches"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-categories--slug--demarches"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Paramètres d’adresse</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>slug</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="slug"                data-endpoint="GETapi-v1-categories--slug--demarches"
               value="etudes"
               data-component="url">
    <br>
<p>Identifiant de la catégorie. Example: <code>etudes</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Paramètres de requête</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-categories--slug--demarches"
               value="50"
               data-component="query">
    <br>
<p>Fiches par page, de 1 à 100 (50 par défaut). Example: <code>50</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>lang</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lang"                data-endpoint="GETapi-v1-categories--slug--demarches"
               value="fr"
               data-component="query">
    <br>
<p>Langue du contenu : fr (par défaut), en ou ar. Example: <code>fr</code></p>
            </div>
                </form>

                <h1 id="recherche">Recherche</h1>

    

                                <h2 id="recherche-GETapi-v1-search-suggest">Suggestions de recherche</h2>

<p>
</p>

<p>Jusqu’à 8 fiches correspondant au début de saisie, avec extrait et catégorie. Rien en dessous de 3 caractères.</p>

<span id="example-requests-GETapi-v1-search-suggest">
<blockquote>Exemple de requête:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/search/suggest?q=passe&amp;lang=fr" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/search/suggest"
);

const params = {
    "q": "passe",
    "lang": "fr",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-search-suggest">
            <blockquote>
            <p>Exemple de réponse (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
content-security-policy: default-src &#039;none&#039;; frame-ancestors &#039;none&#039;; base-uri &#039;none&#039;; form-action &#039;none&#039;
x-content-type-options: nosniff
x-frame-options: DENY
referrer-policy: strict-origin-when-cross-origin
permissions-policy: geolocation=(), microphone=(), camera=()
cross-origin-resource-policy: same-site
x-ratelimit-limit: 100
x-ratelimit-remaining: 99
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: 8,
            &quot;title&quot;: &quot;Passeport Biom&eacute;trique&quot;,
            &quot;slug&quot;: &quot;passeport-biometrique&quot;,
            &quot;excerpt&quot;: &quot;Obtenir ou renouveler votre passeport biom&eacute;trique tchadien&quot;,
            &quot;category&quot;: &quot;Voyage et Visa&quot;,
            &quot;institution&quot;: &quot;Agence Nationale des Titres S&eacute;curis&eacute;s (ANATS)&quot;,
            &quot;cost&quot;: &quot;60 000 FCFA&quot;,
            &quot;cost_amount&quot;: 60000,
            &quot;processing_time&quot;: &quot;15-30 jours&quot;
        },
        {
            &quot;id&quot;: 9,
            &quot;title&quot;: &quot;Acte de Naissance&quot;,
            &quot;slug&quot;: &quot;acte-de-naissance&quot;,
            &quot;excerpt&quot;: &quot;Obtenir un extrait ou une copie int&eacute;grale de votre acte de naissance&quot;,
            &quot;category&quot;: &quot;&Eacute;tat civil&quot;,
            &quot;institution&quot;: &quot;Centre d&#039;&Eacute;tat Civil &mdash; Mairie de votre commune&quot;,
            &quot;cost&quot;: &quot;1&nbsp;000 FCFA&quot;,
            &quot;cost_amount&quot;: 1000,
            &quot;processing_time&quot;: &quot;1-5 jours&quot;
        },
        {
            &quot;id&quot;: 11,
            &quot;title&quot;: &quot;Casier Judiciaire&quot;,
            &quot;slug&quot;: &quot;casier-judiciaire&quot;,
            &quot;excerpt&quot;: &quot;Obtenir un extrait de casier judiciaire (bulletin n&deg;3)&quot;,
            &quot;category&quot;: &quot;Justice&quot;,
            &quot;institution&quot;: &quot;Tribunal de Grande Instance de N&#039;Djam&eacute;na&quot;,
            &quot;cost&quot;: &quot;2&nbsp;000 FCFA&quot;,
            &quot;cost_amount&quot;: 2000,
            &quot;processing_time&quot;: &quot;3-7 jours&quot;
        }
    ]
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-search-suggest" hidden>
    <blockquote>Réponse reçue<span
                id="execution-response-status-GETapi-v1-search-suggest"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-search-suggest"
      data-empty-response-text="<Réponse vide>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-search-suggest" hidden>
    <blockquote>La requête a échoué:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-search-suggest">

Vérifiez votre connexion. Les détails de l’erreur sont dans la console du navigateur.</code></pre>
</span>
<form id="form-GETapi-v1-search-suggest" data-method="GET"
      data-path="api/v1/search/suggest"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-search-suggest', this);">
    <h3>
        Requête&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-search-suggest"
                    onclick="tryItOut('GETapi-v1-search-suggest');">Essayer
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-search-suggest"
                    onclick="cancelTryOut('GETapi-v1-search-suggest');" hidden>Annuler
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-search-suggest"
                    data-initial-text="Envoyer la requête"
                    data-loading-text="Envoi…"
                    hidden>Envoyer la requête
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/search/suggest</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>En-têtes</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-search-suggest"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-search-suggest"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Paramètres de requête</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>q</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="q"                data-endpoint="GETapi-v1-search-suggest"
               value="passe"
               data-component="query">
    <br>
<p>Texte saisi (3 caractères minimum). Example: <code>passe</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>lang</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lang"                data-endpoint="GETapi-v1-search-suggest"
               value="fr"
               data-component="query">
    <br>
<p>Langue : fr (par défaut), en ou ar. Example: <code>fr</code></p>
            </div>
                </form>

                <h1 id="villes-et-bureaux">Villes et bureaux</h1>

    

                                <h2 id="villes-et-bureaux-GETapi-v1-cities">Lister les villes</h2>

<p>
</p>

<p>Villes actives avec le nombre de bureaux administratifs enregistrés dans chacune.</p>

<span id="example-requests-GETapi-v1-cities">
<blockquote>Exemple de requête:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/cities?lang=fr" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/cities"
);

const params = {
    "lang": "fr",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-cities">
            <blockquote>
            <p>Exemple de réponse (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
content-security-policy: default-src &#039;none&#039;; frame-ancestors &#039;none&#039;; base-uri &#039;none&#039;; form-action &#039;none&#039;
x-content-type-options: nosniff
x-frame-options: DENY
referrer-policy: strict-origin-when-cross-origin
permissions-policy: geolocation=(), microphone=(), camera=()
cross-origin-resource-policy: same-site
x-ratelimit-limit: 100
x-ratelimit-remaining: 99
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;slug&quot;: &quot;ndjamena&quot;,
            &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;,
            &quot;province&quot;: &quot;Ville de N&#039;Djam&eacute;na&quot;,
            &quot;offices_count&quot;: 7
        },
        {
            &quot;id&quot;: 2,
            &quot;slug&quot;: &quot;moundou&quot;,
            &quot;name&quot;: &quot;Moundou&quot;,
            &quot;province&quot;: &quot;Logone Occidental&quot;,
            &quot;offices_count&quot;: 0
        },
        {
            &quot;id&quot;: 3,
            &quot;slug&quot;: &quot;sarh&quot;,
            &quot;name&quot;: &quot;Sarh&quot;,
            &quot;province&quot;: &quot;Moyen-Chari&quot;,
            &quot;offices_count&quot;: 0
        },
        {
            &quot;id&quot;: 4,
            &quot;slug&quot;: &quot;abeche&quot;,
            &quot;name&quot;: &quot;Ab&eacute;ch&eacute;&quot;,
            &quot;province&quot;: &quot;Ouadda&iuml;&quot;,
            &quot;offices_count&quot;: 0
        },
        {
            &quot;id&quot;: 22,
            &quot;slug&quot;: &quot;am-timan&quot;,
            &quot;name&quot;: &quot;Am Timan&quot;,
            &quot;province&quot;: &quot;Salamat&quot;,
            &quot;offices_count&quot;: 0
        },
        {
            &quot;id&quot;: 13,
            &quot;slug&quot;: &quot;amdjarass&quot;,
            &quot;name&quot;: &quot;Amdjarass&quot;,
            &quot;province&quot;: &quot;Ennedi Est&quot;,
            &quot;offices_count&quot;: 0
        },
        {
            &quot;id&quot;: 5,
            &quot;slug&quot;: &quot;ati&quot;,
            &quot;name&quot;: &quot;Ati&quot;,
            &quot;province&quot;: &quot;Batha&quot;,
            &quot;offices_count&quot;: 0
        },
        {
            &quot;id&quot;: 6,
            &quot;slug&quot;: &quot;bardai&quot;,
            &quot;name&quot;: &quot;Barda&iuml;&quot;,
            &quot;province&quot;: &quot;Tibesti&quot;,
            &quot;offices_count&quot;: 0
        },
        {
            &quot;id&quot;: 7,
            &quot;slug&quot;: &quot;biltine&quot;,
            &quot;name&quot;: &quot;Biltine&quot;,
            &quot;province&quot;: &quot;Wadi Fira&quot;,
            &quot;offices_count&quot;: 0
        },
        {
            &quot;id&quot;: 8,
            &quot;slug&quot;: &quot;bol&quot;,
            &quot;name&quot;: &quot;Bol&quot;,
            &quot;province&quot;: &quot;Lac&quot;,
            &quot;offices_count&quot;: 0
        },
        {
            &quot;id&quot;: 9,
            &quot;slug&quot;: &quot;bongor&quot;,
            &quot;name&quot;: &quot;Bongor&quot;,
            &quot;province&quot;: &quot;Mayo-Kebbi Est&quot;,
            &quot;offices_count&quot;: 1
        },
        {
            &quot;id&quot;: 10,
            &quot;slug&quot;: &quot;doba&quot;,
            &quot;name&quot;: &quot;Doba&quot;,
            &quot;province&quot;: &quot;Logone Oriental&quot;,
            &quot;offices_count&quot;: 0
        },
        {
            &quot;id&quot;: 12,
            &quot;slug&quot;: &quot;fada&quot;,
            &quot;name&quot;: &quot;Fada&quot;,
            &quot;province&quot;: &quot;Ennedi Ouest&quot;,
            &quot;offices_count&quot;: 0
        },
        {
            &quot;id&quot;: 11,
            &quot;slug&quot;: &quot;faya-largeau&quot;,
            &quot;name&quot;: &quot;Faya-Largeau&quot;,
            &quot;province&quot;: &quot;Borkou&quot;,
            &quot;offices_count&quot;: 0
        },
        {
            &quot;id&quot;: 14,
            &quot;slug&quot;: &quot;goz-beida&quot;,
            &quot;name&quot;: &quot;Goz Be&iuml;da&quot;,
            &quot;province&quot;: &quot;Sila&quot;,
            &quot;offices_count&quot;: 0
        },
        {
            &quot;id&quot;: 15,
            &quot;slug&quot;: &quot;koumra&quot;,
            &quot;name&quot;: &quot;Koumra&quot;,
            &quot;province&quot;: &quot;Mandoul&quot;,
            &quot;offices_count&quot;: 0
        },
        {
            &quot;id&quot;: 16,
            &quot;slug&quot;: &quot;lai&quot;,
            &quot;name&quot;: &quot;La&iuml;&quot;,
            &quot;province&quot;: &quot;Tandjil&eacute;&quot;,
            &quot;offices_count&quot;: 0
        },
        {
            &quot;id&quot;: 17,
            &quot;slug&quot;: &quot;mao&quot;,
            &quot;name&quot;: &quot;Mao&quot;,
            &quot;province&quot;: &quot;Kanem&quot;,
            &quot;offices_count&quot;: 0
        },
        {
            &quot;id&quot;: 18,
            &quot;slug&quot;: &quot;massakory&quot;,
            &quot;name&quot;: &quot;Massakory&quot;,
            &quot;province&quot;: &quot;Hadjer-Lamis&quot;,
            &quot;offices_count&quot;: 0
        },
        {
            &quot;id&quot;: 23,
            &quot;slug&quot;: &quot;massenya&quot;,
            &quot;name&quot;: &quot;Massenya&quot;,
            &quot;province&quot;: &quot;Chari-Baguirmi&quot;,
            &quot;offices_count&quot;: 0
        },
        {
            &quot;id&quot;: 19,
            &quot;slug&quot;: &quot;mongo&quot;,
            &quot;name&quot;: &quot;Mongo&quot;,
            &quot;province&quot;: &quot;Gu&eacute;ra&quot;,
            &quot;offices_count&quot;: 1
        },
        {
            &quot;id&quot;: 20,
            &quot;slug&quot;: &quot;moussoro&quot;,
            &quot;name&quot;: &quot;Moussoro&quot;,
            &quot;province&quot;: &quot;Barh El Gazel&quot;,
            &quot;offices_count&quot;: 0
        },
        {
            &quot;id&quot;: 21,
            &quot;slug&quot;: &quot;pala&quot;,
            &quot;name&quot;: &quot;Pala&quot;,
            &quot;province&quot;: &quot;Mayo-Kebbi Ouest&quot;,
            &quot;offices_count&quot;: 0
        },
        {
            &quot;id&quot;: 24,
            &quot;slug&quot;: &quot;kelo&quot;,
            &quot;name&quot;: &quot;K&eacute;lo&quot;,
            &quot;province&quot;: &quot;Tandjil&eacute;&quot;,
            &quot;offices_count&quot;: 0
        }
    ]
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-cities" hidden>
    <blockquote>Réponse reçue<span
                id="execution-response-status-GETapi-v1-cities"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-cities"
      data-empty-response-text="<Réponse vide>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-cities" hidden>
    <blockquote>La requête a échoué:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-cities">

Vérifiez votre connexion. Les détails de l’erreur sont dans la console du navigateur.</code></pre>
</span>
<form id="form-GETapi-v1-cities" data-method="GET"
      data-path="api/v1/cities"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-cities', this);">
    <h3>
        Requête&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-cities"
                    onclick="tryItOut('GETapi-v1-cities');">Essayer
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-cities"
                    onclick="cancelTryOut('GETapi-v1-cities');" hidden>Annuler
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-cities"
                    data-initial-text="Envoyer la requête"
                    data-loading-text="Envoi…"
                    hidden>Envoyer la requête
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/cities</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>En-têtes</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-cities"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-cities"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Paramètres de requête</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>lang</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lang"                data-endpoint="GETapi-v1-cities"
               value="fr"
               data-component="query">
    <br>
<p>Langue des textes : fr (par défaut), en ou ar. Example: <code>fr</code></p>
            </div>
                </form>

                    <h2 id="villes-et-bureaux-GETapi-v1-cities--slug-">Consulter une ville</h2>

<p>
</p>

<p>La ville, ses bureaux (adresse, horaires, contacts vérifiés, source) et les démarches qu’on peut y faire.</p>

<span id="example-requests-GETapi-v1-cities--slug-">
<blockquote>Exemple de requête:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/cities/ndjamena?lang=fr" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/cities/ndjamena"
);

const params = {
    "lang": "fr",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-cities--slug-">
            <blockquote>
            <p>Exemple de réponse (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
content-security-policy: default-src &#039;none&#039;; frame-ancestors &#039;none&#039;; base-uri &#039;none&#039;; form-action &#039;none&#039;
x-content-type-options: nosniff
x-frame-options: DENY
referrer-policy: strict-origin-when-cross-origin
permissions-policy: geolocation=(), microphone=(), camera=()
cross-origin-resource-policy: same-site
x-ratelimit-limit: 100
x-ratelimit-remaining: 99
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;slug&quot;: &quot;ndjamena&quot;,
        &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;,
        &quot;province&quot;: &quot;Ville de N&#039;Djam&eacute;na&quot;,
        &quot;offices&quot;: [
            {
                &quot;id&quot;: 17,
                &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 10e arrondissement&quot;,
                &quot;address&quot;: null,
                &quot;opening_hours&quot;: null,
                &quot;phone&quot;: null,
                &quot;latitude&quot;: null,
                &quot;longitude&quot;: null,
                &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%2010e%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                &quot;is_active&quot;: true,
                &quot;city&quot;: {
                    &quot;id&quot;: 1,
                    &quot;slug&quot;: &quot;ndjamena&quot;,
                    &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                },
                &quot;demarches&quot;: [
                    {
                        &quot;slug&quot;: &quot;carte-didentite-nationale&quot;,
                        &quot;title&quot;: &quot;Carte d&#039;Identit&eacute; Nationale&quot;
                    },
                    {
                        &quot;slug&quot;: &quot;passeport-biometrique&quot;,
                        &quot;title&quot;: &quot;Passeport Biom&eacute;trique&quot;
                    }
                ]
            },
            {
                &quot;id&quot;: 11,
                &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 1er arrondissement&quot;,
                &quot;address&quot;: null,
                &quot;opening_hours&quot;: null,
                &quot;phone&quot;: null,
                &quot;latitude&quot;: null,
                &quot;longitude&quot;: null,
                &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%201er%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                &quot;is_active&quot;: true,
                &quot;city&quot;: {
                    &quot;id&quot;: 1,
                    &quot;slug&quot;: &quot;ndjamena&quot;,
                    &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                },
                &quot;demarches&quot;: [
                    {
                        &quot;slug&quot;: &quot;carte-didentite-nationale&quot;,
                        &quot;title&quot;: &quot;Carte d&#039;Identit&eacute; Nationale&quot;
                    },
                    {
                        &quot;slug&quot;: &quot;passeport-biometrique&quot;,
                        &quot;title&quot;: &quot;Passeport Biom&eacute;trique&quot;
                    }
                ]
            },
            {
                &quot;id&quot;: 12,
                &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 2e arrondissement&quot;,
                &quot;address&quot;: null,
                &quot;opening_hours&quot;: null,
                &quot;phone&quot;: null,
                &quot;latitude&quot;: null,
                &quot;longitude&quot;: null,
                &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%202e%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                &quot;is_active&quot;: true,
                &quot;city&quot;: {
                    &quot;id&quot;: 1,
                    &quot;slug&quot;: &quot;ndjamena&quot;,
                    &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                },
                &quot;demarches&quot;: [
                    {
                        &quot;slug&quot;: &quot;carte-didentite-nationale&quot;,
                        &quot;title&quot;: &quot;Carte d&#039;Identit&eacute; Nationale&quot;
                    },
                    {
                        &quot;slug&quot;: &quot;passeport-biometrique&quot;,
                        &quot;title&quot;: &quot;Passeport Biom&eacute;trique&quot;
                    }
                ]
            },
            {
                &quot;id&quot;: 13,
                &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 5e arrondissement&quot;,
                &quot;address&quot;: null,
                &quot;opening_hours&quot;: null,
                &quot;phone&quot;: null,
                &quot;latitude&quot;: null,
                &quot;longitude&quot;: null,
                &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%205e%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                &quot;is_active&quot;: true,
                &quot;city&quot;: {
                    &quot;id&quot;: 1,
                    &quot;slug&quot;: &quot;ndjamena&quot;,
                    &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                },
                &quot;demarches&quot;: [
                    {
                        &quot;slug&quot;: &quot;carte-didentite-nationale&quot;,
                        &quot;title&quot;: &quot;Carte d&#039;Identit&eacute; Nationale&quot;
                    },
                    {
                        &quot;slug&quot;: &quot;passeport-biometrique&quot;,
                        &quot;title&quot;: &quot;Passeport Biom&eacute;trique&quot;
                    }
                ]
            },
            {
                &quot;id&quot;: 14,
                &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 6e arrondissement&quot;,
                &quot;address&quot;: null,
                &quot;opening_hours&quot;: null,
                &quot;phone&quot;: null,
                &quot;latitude&quot;: null,
                &quot;longitude&quot;: null,
                &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%206e%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                &quot;is_active&quot;: true,
                &quot;city&quot;: {
                    &quot;id&quot;: 1,
                    &quot;slug&quot;: &quot;ndjamena&quot;,
                    &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                },
                &quot;demarches&quot;: [
                    {
                        &quot;slug&quot;: &quot;carte-didentite-nationale&quot;,
                        &quot;title&quot;: &quot;Carte d&#039;Identit&eacute; Nationale&quot;
                    },
                    {
                        &quot;slug&quot;: &quot;passeport-biometrique&quot;,
                        &quot;title&quot;: &quot;Passeport Biom&eacute;trique&quot;
                    }
                ]
            },
            {
                &quot;id&quot;: 15,
                &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 7e arrondissement&quot;,
                &quot;address&quot;: null,
                &quot;opening_hours&quot;: null,
                &quot;phone&quot;: null,
                &quot;latitude&quot;: null,
                &quot;longitude&quot;: null,
                &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%207e%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                &quot;is_active&quot;: true,
                &quot;city&quot;: {
                    &quot;id&quot;: 1,
                    &quot;slug&quot;: &quot;ndjamena&quot;,
                    &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                },
                &quot;demarches&quot;: [
                    {
                        &quot;slug&quot;: &quot;carte-didentite-nationale&quot;,
                        &quot;title&quot;: &quot;Carte d&#039;Identit&eacute; Nationale&quot;
                    },
                    {
                        &quot;slug&quot;: &quot;passeport-biometrique&quot;,
                        &quot;title&quot;: &quot;Passeport Biom&eacute;trique&quot;
                    }
                ]
            },
            {
                &quot;id&quot;: 16,
                &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 8e arrondissement&quot;,
                &quot;address&quot;: null,
                &quot;opening_hours&quot;: null,
                &quot;phone&quot;: null,
                &quot;latitude&quot;: null,
                &quot;longitude&quot;: null,
                &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%208e%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                &quot;is_active&quot;: true,
                &quot;city&quot;: {
                    &quot;id&quot;: 1,
                    &quot;slug&quot;: &quot;ndjamena&quot;,
                    &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                },
                &quot;demarches&quot;: [
                    {
                        &quot;slug&quot;: &quot;carte-didentite-nationale&quot;,
                        &quot;title&quot;: &quot;Carte d&#039;Identit&eacute; Nationale&quot;
                    },
                    {
                        &quot;slug&quot;: &quot;passeport-biometrique&quot;,
                        &quot;title&quot;: &quot;Passeport Biom&eacute;trique&quot;
                    }
                ]
            }
        ]
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-cities--slug-" hidden>
    <blockquote>Réponse reçue<span
                id="execution-response-status-GETapi-v1-cities--slug-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-cities--slug-"
      data-empty-response-text="<Réponse vide>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-cities--slug-" hidden>
    <blockquote>La requête a échoué:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-cities--slug-">

Vérifiez votre connexion. Les détails de l’erreur sont dans la console du navigateur.</code></pre>
</span>
<form id="form-GETapi-v1-cities--slug-" data-method="GET"
      data-path="api/v1/cities/{slug}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-cities--slug-', this);">
    <h3>
        Requête&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-cities--slug-"
                    onclick="tryItOut('GETapi-v1-cities--slug-');">Essayer
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-cities--slug-"
                    onclick="cancelTryOut('GETapi-v1-cities--slug-');" hidden>Annuler
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-cities--slug-"
                    data-initial-text="Envoyer la requête"
                    data-loading-text="Envoi…"
                    hidden>Envoyer la requête
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/cities/{slug}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>En-têtes</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-cities--slug-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-cities--slug-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Paramètres d’adresse</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>slug</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="slug"                data-endpoint="GETapi-v1-cities--slug-"
               value="ndjamena"
               data-component="url">
    <br>
<p>Identifiant de la ville. Example: <code>ndjamena</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Paramètres de requête</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>lang</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lang"                data-endpoint="GETapi-v1-cities--slug-"
               value="fr"
               data-component="query">
    <br>
<p>Langue des textes : fr (par défaut), en ou ar. Example: <code>fr</code></p>
            </div>
                </form>

                <h1 id="parcours-de-vie">Parcours de vie</h1>

    

                                <h2 id="parcours-de-vie-GETapi-v1-life-events">Lister les parcours de vie</h2>

<p>
</p>

<p>Situations de vie (naissance, mariage, voyage…) regroupant plusieurs démarches.</p>

<span id="example-requests-GETapi-v1-life-events">
<blockquote>Exemple de requête:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/life-events?lang=fr" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/life-events"
);

const params = {
    "lang": "fr",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-life-events">
            <blockquote>
            <p>Exemple de réponse (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
content-security-policy: default-src &#039;none&#039;; frame-ancestors &#039;none&#039;; base-uri &#039;none&#039;; form-action &#039;none&#039;
x-content-type-options: nosniff
x-frame-options: DENY
referrer-policy: strict-origin-when-cross-origin
permissions-policy: geolocation=(), microphone=(), camera=()
cross-origin-resource-policy: same-site
x-ratelimit-limit: 100
x-ratelimit-remaining: 99
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;slug&quot;: &quot;voyager-a-l-etranger&quot;,
            &quot;title&quot;: &quot;Je veux voyager &agrave; l&#039;&eacute;tranger&quot;,
            &quot;description&quot;: &quot;Le passeport demande un acte de naissance, une carte d&rsquo;identit&eacute; et un casier judiciaire : faites-les dans cet ordre.&quot;,
            &quot;icon&quot;: &quot;plane&quot;,
            &quot;steps_count&quot;: 4
        },
        {
            &quot;slug&quot;: &quot;obtenir-mes-papiers&quot;,
            &quot;title&quot;: &quot;J&#039;ai besoin de mes papiers d&#039;identit&eacute;&quot;,
            &quot;description&quot;: &quot;La carte nationale d&rsquo;identit&eacute; demande d&rsquo;abord un certificat de naissance.&quot;,
            &quot;icon&quot;: &quot;id-card&quot;,
            &quot;steps_count&quot;: 2
        },
        {
            &quot;slug&quot;: &quot;j-ai-un-bebe&quot;,
            &quot;title&quot;: &quot;J&#039;ai un b&eacute;b&eacute;&quot;,
            &quot;description&quot;: &quot;D&eacute;clarez la naissance et obtenez l&rsquo;acte de naissance : c&rsquo;est le premier document de votre enfant.&quot;,
            &quot;icon&quot;: &quot;baby&quot;,
            &quot;steps_count&quot;: 1
        },
        {
            &quot;slug&quot;: &quot;je-me-marie&quot;,
            &quot;title&quot;: &quot;Je me marie&quot;,
            &quot;description&quot;: &quot;Le certificat de mariage demande une pi&egrave;ce d&rsquo;identit&eacute; : pr&eacute;parez-la &agrave; l&rsquo;avance.&quot;,
            &quot;icon&quot;: &quot;heart&quot;,
            &quot;steps_count&quot;: 2
        },
        {
            &quot;slug&quot;: &quot;je-perds-un-proche&quot;,
            &quot;title&quot;: &quot;Je perds un proche&quot;,
            &quot;description&quot;: &quot;L&rsquo;acte de d&eacute;c&egrave;s est indispensable pour la succession, les pensions et la cl&ocirc;ture des comptes.&quot;,
            &quot;icon&quot;: &quot;flower&quot;,
            &quot;steps_count&quot;: 1
        },
        {
            &quot;slug&quot;: &quot;je-veux-voter&quot;,
            &quot;title&quot;: &quot;Je veux voter&quot;,
            &quot;description&quot;: &quot;L&rsquo;inscription sur la liste &eacute;lectorale demande un acte de naissance et une carte d&rsquo;identit&eacute; valide.&quot;,
            &quot;icon&quot;: &quot;vote&quot;,
            &quot;steps_count&quot;: 3
        },
        {
            &quot;slug&quot;: &quot;j-entre-a-l-universite&quot;,
            &quot;title&quot;: &quot;J&#039;entre &agrave; l&#039;universit&eacute;&quot;,
            &quot;description&quot;: &quot;L&rsquo;inscription demande un certificat de naissance et une copie de votre pi&egrave;ce d&rsquo;identit&eacute;.&quot;,
            &quot;icon&quot;: &quot;graduation-cap&quot;,
            &quot;steps_count&quot;: 3
        },
        {
            &quot;slug&quot;: &quot;je-veux-conduire&quot;,
            &quot;title&quot;: &quot;Je veux conduire&quot;,
            &quot;description&quot;: &quot;Le permis de conduire demande une pi&egrave;ce d&rsquo;identit&eacute;.&quot;,
            &quot;icon&quot;: &quot;car&quot;,
            &quot;steps_count&quot;: 2
        },
        {
            &quot;slug&quot;: &quot;je-construis&quot;,
            &quot;title&quot;: &quot;Je construis ma maison&quot;,
            &quot;description&quot;: &quot;Le permis de construire demande une pi&egrave;ce d&rsquo;identit&eacute;, un titre de propri&eacute;t&eacute; et un plan sign&eacute;.&quot;,
            &quot;icon&quot;: &quot;home&quot;,
            &quot;steps_count&quot;: 2
        }
    ]
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-life-events" hidden>
    <blockquote>Réponse reçue<span
                id="execution-response-status-GETapi-v1-life-events"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-life-events"
      data-empty-response-text="<Réponse vide>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-life-events" hidden>
    <blockquote>La requête a échoué:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-life-events">

Vérifiez votre connexion. Les détails de l’erreur sont dans la console du navigateur.</code></pre>
</span>
<form id="form-GETapi-v1-life-events" data-method="GET"
      data-path="api/v1/life-events"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-life-events', this);">
    <h3>
        Requête&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-life-events"
                    onclick="tryItOut('GETapi-v1-life-events');">Essayer
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-life-events"
                    onclick="cancelTryOut('GETapi-v1-life-events');" hidden>Annuler
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-life-events"
                    data-initial-text="Envoyer la requête"
                    data-loading-text="Envoi…"
                    hidden>Envoyer la requête
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/life-events</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>En-têtes</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-life-events"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-life-events"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Paramètres de requête</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>lang</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lang"                data-endpoint="GETapi-v1-life-events"
               value="fr"
               data-component="query">
    <br>
<p>Langue des textes : fr (par défaut), en ou ar. Example: <code>fr</code></p>
            </div>
                </form>

                    <h2 id="parcours-de-vie-GETapi-v1-life-events--slug-">Consulter un parcours de vie</h2>

<p>
</p>

<p>Les démarches du parcours dans l’ordre et le coût total à prévoir.</p>

<span id="example-requests-GETapi-v1-life-events--slug-">
<blockquote>Exemple de requête:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/life-events/voyager-a-l-etranger?city=ndjamena&amp;lang=fr" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/life-events/voyager-a-l-etranger"
);

const params = {
    "city": "ndjamena",
    "lang": "fr",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-life-events--slug-">
            <blockquote>
            <p>Exemple de réponse (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
content-security-policy: default-src &#039;none&#039;; frame-ancestors &#039;none&#039;; base-uri &#039;none&#039;; form-action &#039;none&#039;
x-content-type-options: nosniff
x-frame-options: DENY
referrer-policy: strict-origin-when-cross-origin
permissions-policy: geolocation=(), microphone=(), camera=()
cross-origin-resource-policy: same-site
x-ratelimit-limit: 100
x-ratelimit-remaining: 99
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;slug&quot;: &quot;voyager-a-l-etranger&quot;,
        &quot;title&quot;: &quot;Je veux voyager &agrave; l&#039;&eacute;tranger&quot;,
        &quot;description&quot;: &quot;Le passeport demande un acte de naissance, une carte d&rsquo;identit&eacute; et un casier judiciaire : faites-les dans cet ordre.&quot;,
        &quot;icon&quot;: &quot;plane&quot;,
        &quot;steps&quot;: [
            {
                &quot;position&quot;: 1,
                &quot;slug&quot;: &quot;acte-de-naissance&quot;,
                &quot;title&quot;: &quot;Acte de Naissance&quot;,
                &quot;summary&quot;: &quot;Obtenir un extrait ou une copie int&eacute;grale de votre acte de naissance&quot;,
                &quot;cost&quot;: &quot;1&nbsp;000 FCFA&quot;,
                &quot;processing_time&quot;: &quot;1-5 jours&quot;,
                &quot;required_documents&quot;: [
                    &quot;Livret de famille ou d&eacute;claration de naissance originale&quot;,
                    &quot;Pi&egrave;ce d&#039;identit&eacute; du demandeur (parent ou int&eacute;ress&eacute; majeur)&quot;,
                    &quot;Formulaire de demande d&#039;acte&quot;
                ],
                &quot;location&quot;: &quot;Mairie de N&#039;Djam&eacute;na ou commune de naissance&quot;,
                &quot;cost_estimate&quot;: {
                    &quot;official&quot;: 1000,
                    &quot;extras&quot;: 0,
                    &quot;total&quot;: 1000,
                    &quot;complete&quot;: true
                },
                &quot;abroad_available&quot;: false,
                &quot;offices&quot;: []
            },
            {
                &quot;position&quot;: 2,
                &quot;slug&quot;: &quot;carte-didentite-nationale&quot;,
                &quot;title&quot;: &quot;Carte d&#039;Identit&eacute; Nationale&quot;,
                &quot;summary&quot;: &quot;Demander ou renouveler votre carte d&#039;identit&eacute; nationale&quot;,
                &quot;cost&quot;: &quot;5&nbsp;000 FCFA&quot;,
                &quot;processing_time&quot;: &quot;10-15 jours&quot;,
                &quot;required_documents&quot;: [
                    &quot;Copie de l&#039;acte de naissance&quot;,
                    &quot;Justificatif de profession (si n&eacute;cessaire)&quot;,
                    &quot;Deux photos d&#039;identit&eacute; en couleur&quot;,
                    &quot;Trois copies de cartes d&#039;identit&eacute; de t&eacute;moins&quot;
                ],
                &quot;location&quot;: &quot;Centres d&#039;accueil de l&#039;ANATS (N&#039;Djam&eacute;na et chefs-lieux de province)&quot;,
                &quot;cost_estimate&quot;: {
                    &quot;official&quot;: 5000,
                    &quot;extras&quot;: 0,
                    &quot;total&quot;: 5000,
                    &quot;complete&quot;: true
                },
                &quot;abroad_available&quot;: false,
                &quot;offices&quot;: [
                    {
                        &quot;id&quot;: 11,
                        &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 1er arrondissement&quot;,
                        &quot;address&quot;: null,
                        &quot;opening_hours&quot;: null,
                        &quot;phone&quot;: null,
                        &quot;latitude&quot;: null,
                        &quot;longitude&quot;: null,
                        &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%201er%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                        &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                        &quot;is_active&quot;: true,
                        &quot;city&quot;: {
                            &quot;id&quot;: 1,
                            &quot;slug&quot;: &quot;ndjamena&quot;,
                            &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                        }
                    },
                    {
                        &quot;id&quot;: 12,
                        &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 2e arrondissement&quot;,
                        &quot;address&quot;: null,
                        &quot;opening_hours&quot;: null,
                        &quot;phone&quot;: null,
                        &quot;latitude&quot;: null,
                        &quot;longitude&quot;: null,
                        &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%202e%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                        &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                        &quot;is_active&quot;: true,
                        &quot;city&quot;: {
                            &quot;id&quot;: 1,
                            &quot;slug&quot;: &quot;ndjamena&quot;,
                            &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                        }
                    },
                    {
                        &quot;id&quot;: 13,
                        &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 5e arrondissement&quot;,
                        &quot;address&quot;: null,
                        &quot;opening_hours&quot;: null,
                        &quot;phone&quot;: null,
                        &quot;latitude&quot;: null,
                        &quot;longitude&quot;: null,
                        &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%205e%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                        &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                        &quot;is_active&quot;: true,
                        &quot;city&quot;: {
                            &quot;id&quot;: 1,
                            &quot;slug&quot;: &quot;ndjamena&quot;,
                            &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                        }
                    },
                    {
                        &quot;id&quot;: 14,
                        &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 6e arrondissement&quot;,
                        &quot;address&quot;: null,
                        &quot;opening_hours&quot;: null,
                        &quot;phone&quot;: null,
                        &quot;latitude&quot;: null,
                        &quot;longitude&quot;: null,
                        &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%206e%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                        &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                        &quot;is_active&quot;: true,
                        &quot;city&quot;: {
                            &quot;id&quot;: 1,
                            &quot;slug&quot;: &quot;ndjamena&quot;,
                            &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                        }
                    },
                    {
                        &quot;id&quot;: 15,
                        &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 7e arrondissement&quot;,
                        &quot;address&quot;: null,
                        &quot;opening_hours&quot;: null,
                        &quot;phone&quot;: null,
                        &quot;latitude&quot;: null,
                        &quot;longitude&quot;: null,
                        &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%207e%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                        &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                        &quot;is_active&quot;: true,
                        &quot;city&quot;: {
                            &quot;id&quot;: 1,
                            &quot;slug&quot;: &quot;ndjamena&quot;,
                            &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                        }
                    },
                    {
                        &quot;id&quot;: 16,
                        &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 8e arrondissement&quot;,
                        &quot;address&quot;: null,
                        &quot;opening_hours&quot;: null,
                        &quot;phone&quot;: null,
                        &quot;latitude&quot;: null,
                        &quot;longitude&quot;: null,
                        &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%208e%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                        &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                        &quot;is_active&quot;: true,
                        &quot;city&quot;: {
                            &quot;id&quot;: 1,
                            &quot;slug&quot;: &quot;ndjamena&quot;,
                            &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                        }
                    },
                    {
                        &quot;id&quot;: 17,
                        &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 10e arrondissement&quot;,
                        &quot;address&quot;: null,
                        &quot;opening_hours&quot;: null,
                        &quot;phone&quot;: null,
                        &quot;latitude&quot;: null,
                        &quot;longitude&quot;: null,
                        &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%2010e%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                        &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                        &quot;is_active&quot;: true,
                        &quot;city&quot;: {
                            &quot;id&quot;: 1,
                            &quot;slug&quot;: &quot;ndjamena&quot;,
                            &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                        }
                    }
                ]
            },
            {
                &quot;position&quot;: 3,
                &quot;slug&quot;: &quot;casier-judiciaire&quot;,
                &quot;title&quot;: &quot;Casier Judiciaire&quot;,
                &quot;summary&quot;: &quot;Obtenir un extrait de casier judiciaire (bulletin n&deg;3)&quot;,
                &quot;cost&quot;: &quot;2&nbsp;000 FCFA&quot;,
                &quot;processing_time&quot;: &quot;3-7 jours&quot;,
                &quot;required_documents&quot;: [
                    &quot;Carte d&#039;identit&eacute; nationale ou passeport valide&quot;,
                    &quot;Extrait d&#039;acte de naissance&quot;,
                    &quot;Formulaire de demande rempli&quot;,
                    &quot;Re&ccedil;u de paiement des frais&quot;
                ],
                &quot;location&quot;: &quot;Palais de Justice, Avenue F&eacute;lix &Eacute;bou&eacute;, N&#039;Djam&eacute;na&quot;,
                &quot;cost_estimate&quot;: {
                    &quot;official&quot;: 2000,
                    &quot;extras&quot;: 0,
                    &quot;total&quot;: 2000,
                    &quot;complete&quot;: true
                },
                &quot;abroad_available&quot;: false,
                &quot;offices&quot;: []
            },
            {
                &quot;position&quot;: 4,
                &quot;slug&quot;: &quot;passeport-biometrique&quot;,
                &quot;title&quot;: &quot;Passeport Biom&eacute;trique&quot;,
                &quot;summary&quot;: &quot;Obtenir ou renouveler votre passeport biom&eacute;trique tchadien&quot;,
                &quot;cost&quot;: &quot;60 000 FCFA&quot;,
                &quot;processing_time&quot;: &quot;15-30 jours&quot;,
                &quot;required_documents&quot;: [
                    &quot;Acte de naissance (original ou copie l&eacute;galis&eacute;e)&quot;,
                    &quot;Carte d&#039;identit&eacute; nationale valide&quot;,
                    &quot;Deux photos biom&eacute;triques r&eacute;centes (fond blanc, 4x6)&quot;,
                    &quot;Extrait de casier judiciaire vierge (moins de 3 mois)&quot;,
                    &quot;Re&ccedil;u de paiement des frais&quot;,
                    &quot;Ancien passeport (si renouvellement)&quot;
                ],
                &quot;location&quot;: &quot;Centres d&#039;accueil de l&#039;ANATS (N&#039;Djam&eacute;na et chefs-lieux de province)&quot;,
                &quot;cost_estimate&quot;: {
                    &quot;official&quot;: 60000,
                    &quot;extras&quot;: 0,
                    &quot;total&quot;: 60000,
                    &quot;complete&quot;: true
                },
                &quot;abroad_available&quot;: false,
                &quot;offices&quot;: [
                    {
                        &quot;id&quot;: 11,
                        &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 1er arrondissement&quot;,
                        &quot;address&quot;: null,
                        &quot;opening_hours&quot;: null,
                        &quot;phone&quot;: null,
                        &quot;latitude&quot;: null,
                        &quot;longitude&quot;: null,
                        &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%201er%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                        &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                        &quot;is_active&quot;: true,
                        &quot;city&quot;: {
                            &quot;id&quot;: 1,
                            &quot;slug&quot;: &quot;ndjamena&quot;,
                            &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                        }
                    },
                    {
                        &quot;id&quot;: 12,
                        &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 2e arrondissement&quot;,
                        &quot;address&quot;: null,
                        &quot;opening_hours&quot;: null,
                        &quot;phone&quot;: null,
                        &quot;latitude&quot;: null,
                        &quot;longitude&quot;: null,
                        &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%202e%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                        &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                        &quot;is_active&quot;: true,
                        &quot;city&quot;: {
                            &quot;id&quot;: 1,
                            &quot;slug&quot;: &quot;ndjamena&quot;,
                            &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                        }
                    },
                    {
                        &quot;id&quot;: 13,
                        &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 5e arrondissement&quot;,
                        &quot;address&quot;: null,
                        &quot;opening_hours&quot;: null,
                        &quot;phone&quot;: null,
                        &quot;latitude&quot;: null,
                        &quot;longitude&quot;: null,
                        &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%205e%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                        &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                        &quot;is_active&quot;: true,
                        &quot;city&quot;: {
                            &quot;id&quot;: 1,
                            &quot;slug&quot;: &quot;ndjamena&quot;,
                            &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                        }
                    },
                    {
                        &quot;id&quot;: 14,
                        &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 6e arrondissement&quot;,
                        &quot;address&quot;: null,
                        &quot;opening_hours&quot;: null,
                        &quot;phone&quot;: null,
                        &quot;latitude&quot;: null,
                        &quot;longitude&quot;: null,
                        &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%206e%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                        &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                        &quot;is_active&quot;: true,
                        &quot;city&quot;: {
                            &quot;id&quot;: 1,
                            &quot;slug&quot;: &quot;ndjamena&quot;,
                            &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                        }
                    },
                    {
                        &quot;id&quot;: 15,
                        &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 7e arrondissement&quot;,
                        &quot;address&quot;: null,
                        &quot;opening_hours&quot;: null,
                        &quot;phone&quot;: null,
                        &quot;latitude&quot;: null,
                        &quot;longitude&quot;: null,
                        &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%207e%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                        &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                        &quot;is_active&quot;: true,
                        &quot;city&quot;: {
                            &quot;id&quot;: 1,
                            &quot;slug&quot;: &quot;ndjamena&quot;,
                            &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                        }
                    },
                    {
                        &quot;id&quot;: 16,
                        &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 8e arrondissement&quot;,
                        &quot;address&quot;: null,
                        &quot;opening_hours&quot;: null,
                        &quot;phone&quot;: null,
                        &quot;latitude&quot;: null,
                        &quot;longitude&quot;: null,
                        &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%208e%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                        &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                        &quot;is_active&quot;: true,
                        &quot;city&quot;: {
                            &quot;id&quot;: 1,
                            &quot;slug&quot;: &quot;ndjamena&quot;,
                            &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                        }
                    },
                    {
                        &quot;id&quot;: 17,
                        &quot;name&quot;: &quot;Centre d&#039;accueil ANATS du 10e arrondissement&quot;,
                        &quot;address&quot;: null,
                        &quot;opening_hours&quot;: null,
                        &quot;phone&quot;: null,
                        &quot;latitude&quot;: null,
                        &quot;longitude&quot;: null,
                        &quot;directions_url&quot;: &quot;https://www.google.com/maps/dir/?api=1&amp;destination=Centre%20d%27accueil%20ANATS%20du%2010e%20arrondissement%2C%20N%27Djam%C3%A9na%2C%20Tchad&quot;,
                        &quot;source_url&quot;: &quot;https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/&quot;,
                        &quot;is_active&quot;: true,
                        &quot;city&quot;: {
                            &quot;id&quot;: 1,
                            &quot;slug&quot;: &quot;ndjamena&quot;,
                            &quot;name&quot;: &quot;N&#039;Djam&eacute;na&quot;
                        }
                    }
                ]
            }
        ],
        &quot;total&quot;: {
            &quot;amount&quot;: 68000,
            &quot;complete&quot;: true
        }
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-life-events--slug-" hidden>
    <blockquote>Réponse reçue<span
                id="execution-response-status-GETapi-v1-life-events--slug-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-life-events--slug-"
      data-empty-response-text="<Réponse vide>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-life-events--slug-" hidden>
    <blockquote>La requête a échoué:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-life-events--slug-">

Vérifiez votre connexion. Les détails de l’erreur sont dans la console du navigateur.</code></pre>
</span>
<form id="form-GETapi-v1-life-events--slug-" data-method="GET"
      data-path="api/v1/life-events/{slug}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-life-events--slug-', this);">
    <h3>
        Requête&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-life-events--slug-"
                    onclick="tryItOut('GETapi-v1-life-events--slug-');">Essayer
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-life-events--slug-"
                    onclick="cancelTryOut('GETapi-v1-life-events--slug-');" hidden>Annuler
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-life-events--slug-"
                    data-initial-text="Envoyer la requête"
                    data-loading-text="Envoi…"
                    hidden>Envoyer la requête
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/life-events/{slug}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>En-têtes</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-life-events--slug-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-life-events--slug-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Paramètres d’adresse</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>slug</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="slug"                data-endpoint="GETapi-v1-life-events--slug-"
               value="voyager-a-l-etranger"
               data-component="url">
    <br>
<p>Identifiant du parcours. Example: <code>voyager-a-l-etranger</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Paramètres de requête</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>city</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="city"                data-endpoint="GETapi-v1-life-events--slug-"
               value="ndjamena"
               data-component="query">
    <br>
<p>Slug d’une ville : ajoute les bureaux de cette ville à chaque étape. Example: <code>ndjamena</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>lang</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lang"                data-endpoint="GETapi-v1-life-events--slug-"
               value="fr"
               data-component="query">
    <br>
<p>Langue des textes : fr (par défaut), en ou ar. Example: <code>fr</code></p>
            </div>
                </form>

                <h1 id="diaspora">Diaspora</h1>

    

                                <h2 id="diaspora-GETapi-v1-diaspora">Diaspora</h2>

<p>
</p>

<p>Démarches possibles depuis l’étranger et représentations diplomatiques du Tchad.</p>

<span id="example-requests-GETapi-v1-diaspora">
<blockquote>Exemple de requête:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/diaspora?lang=fr" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/diaspora"
);

const params = {
    "lang": "fr",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-diaspora">
            <blockquote>
            <p>Exemple de réponse (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
content-security-policy: default-src &#039;none&#039;; frame-ancestors &#039;none&#039;; base-uri &#039;none&#039;; form-action &#039;none&#039;
x-content-type-options: nosniff
x-frame-options: DENY
referrer-policy: strict-origin-when-cross-origin
permissions-policy: geolocation=(), microphone=(), camera=()
cross-origin-resource-policy: same-site
x-ratelimit-limit: 100
x-ratelimit-remaining: 99
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;demarches&quot;: [],
        &quot;representations&quot;: [
            {
                &quot;id&quot;: 8,
                &quot;country&quot;: &quot;Canada&quot;,
                &quot;city&quot;: &quot;Ottawa&quot;,
                &quot;type&quot;: &quot;embassy&quot;,
                &quot;address&quot;: &quot;350 Sparks Street, Suite 802, Ottawa, ON K1R 7S8&quot;,
                &quot;phone&quot;: null,
                &quot;email&quot;: null,
                &quot;website&quot;: null,
                &quot;source_url&quot;: &quot;https://www.international.gc.ca/protocol-protocole/addresses.aspx?lang=eng&quot;,
                &quot;notes&quot;: null
            },
            {
                &quot;id&quot;: 7,
                &quot;country&quot;: &quot;&Eacute;tats-Unis&quot;,
                &quot;city&quot;: &quot;Washington&quot;,
                &quot;type&quot;: &quot;embassy&quot;,
                &quot;address&quot;: &quot;2401 Massachusetts Ave NW, Washington, DC 20008&quot;,
                &quot;phone&quot;: &quot;+1 202 652 1312&quot;,
                &quot;email&quot;: &quot;info@chadembassy.us&quot;,
                &quot;website&quot;: &quot;https://chadembassy.us&quot;,
                &quot;source_url&quot;: &quot;https://chadembassy.us&quot;,
                &quot;notes&quot;: null
            },
            {
                &quot;id&quot;: 6,
                &quot;country&quot;: &quot;France&quot;,
                &quot;city&quot;: &quot;Paris&quot;,
                &quot;type&quot;: &quot;embassy&quot;,
                &quot;address&quot;: &quot;65 rue des Belles Feuilles, 75016 Paris&quot;,
                &quot;phone&quot;: &quot;+33 1 45 53 36 75&quot;,
                &quot;email&quot;: &quot;ambassadedutchadparis@wanadoo.fr&quot;,
                &quot;website&quot;: null,
                &quot;source_url&quot;: &quot;https://www.diplomatie.gouv.fr/fr/services/annuaire-et-contacts/representations-etrangeres/paris-ambassade-du-tchad&quot;,
                &quot;notes&quot;: null
            }
        ]
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-diaspora" hidden>
    <blockquote>Réponse reçue<span
                id="execution-response-status-GETapi-v1-diaspora"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-diaspora"
      data-empty-response-text="<Réponse vide>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-diaspora" hidden>
    <blockquote>La requête a échoué:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-diaspora">

Vérifiez votre connexion. Les détails de l’erreur sont dans la console du navigateur.</code></pre>
</span>
<form id="form-GETapi-v1-diaspora" data-method="GET"
      data-path="api/v1/diaspora"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-diaspora', this);">
    <h3>
        Requête&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-diaspora"
                    onclick="tryItOut('GETapi-v1-diaspora');">Essayer
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-diaspora"
                    onclick="cancelTryOut('GETapi-v1-diaspora');" hidden>Annuler
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-diaspora"
                    data-initial-text="Envoyer la requête"
                    data-loading-text="Envoi…"
                    hidden>Envoyer la requête
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/diaspora</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>En-têtes</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-diaspora"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-diaspora"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Paramètres de requête</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>lang</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lang"                data-endpoint="GETapi-v1-diaspora"
               value="fr"
               data-component="query">
    <br>
<p>Langue des textes : fr (par défaut), en ou ar. Example: <code>fr</code></p>
            </div>
                </form>

                <h1 id="calendrier">Calendrier</h1>

    

                                <h2 id="calendrier-GETapi-v1-calendar">Calendrier administratif</h2>

<p>
</p>

<p>Dates publiées en cours ou à venir. Chaque date indique son type (kind), son statut (ongoing ou upcoming)
et le nombre de jours restants (days_left : jusqu’à la fin d’une période en cours, sinon jusqu’au début).</p>

<span id="example-requests-GETapi-v1-calendar">
<blockquote>Exemple de requête:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/calendar?category=exams&amp;kind=deadline&amp;lang=fr" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/calendar"
);

const params = {
    "category": "exams",
    "kind": "deadline",
    "lang": "fr",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-calendar">
            <blockquote>
            <p>Exemple de réponse (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
content-security-policy: default-src &#039;none&#039;; frame-ancestors &#039;none&#039;; base-uri &#039;none&#039;; form-action &#039;none&#039;
x-content-type-options: nosniff
x-frame-options: DENY
referrer-policy: strict-origin-when-cross-origin
permissions-policy: geolocation=(), microphone=(), camera=()
cross-origin-resource-policy: same-site
x-ratelimit-limit: 100
x-ratelimit-remaining: 99
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: []
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-calendar" hidden>
    <blockquote>Réponse reçue<span
                id="execution-response-status-GETapi-v1-calendar"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-calendar"
      data-empty-response-text="<Réponse vide>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-calendar" hidden>
    <blockquote>La requête a échoué:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-calendar">

Vérifiez votre connexion. Les détails de l’erreur sont dans la console du navigateur.</code></pre>
</span>
<form id="form-GETapi-v1-calendar" data-method="GET"
      data-path="api/v1/calendar"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-calendar', this);">
    <h3>
        Requête&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-calendar"
                    onclick="tryItOut('GETapi-v1-calendar');">Essayer
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-calendar"
                    onclick="cancelTryOut('GETapi-v1-calendar');" hidden>Annuler
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-calendar"
                    data-initial-text="Envoyer la requête"
                    data-loading-text="Envoi…"
                    hidden>Envoyer la requête
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/calendar</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>En-têtes</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-calendar"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-calendar"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Paramètres de requête</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>category</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="category"                data-endpoint="GETapi-v1-calendar"
               value="exams"
               data-component="query">
    <br>
<p>Domaine : elections, education, exams, identity, tax, other. Example: <code>exams</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>kind</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="kind"                data-endpoint="GETapi-v1-calendar"
               value="deadline"
               data-component="query">
    <br>
<p>Type d’échéance : period (période d’inscription…), deadline (date limite), event (date unique). Example: <code>deadline</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>lang</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lang"                data-endpoint="GETapi-v1-calendar"
               value="fr"
               data-component="query">
    <br>
<p>Langue des textes : fr (par défaut), en ou ar. Example: <code>fr</code></p>
            </div>
                </form>

                    <h2 id="calendrier-GETapi-v1-calendar--id--ics">Ajouter une date à son agenda</h2>

<p>
</p>

<p>Fichier iCalendar (.ics). Une date limite reçoit un rappel 3 jours avant.</p>

<span id="example-requests-GETapi-v1-calendar--id--ics">
<blockquote>Exemple de requête:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/calendar/1.ics?lang=fr" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/calendar/1.ics"
);

const params = {
    "lang": "fr",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-calendar--id--ics">
            <blockquote>
            <p>Exemple de réponse (200, Fichier .ics):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">BEGIN:VCALENDAR</code>
 </pre>
            <blockquote>
            <p>Exemple de réponse (404, Date inconnue ou non publiée):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Ressource introuvable.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-calendar--id--ics" hidden>
    <blockquote>Réponse reçue<span
                id="execution-response-status-GETapi-v1-calendar--id--ics"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-calendar--id--ics"
      data-empty-response-text="<Réponse vide>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-calendar--id--ics" hidden>
    <blockquote>La requête a échoué:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-calendar--id--ics">

Vérifiez votre connexion. Les détails de l’erreur sont dans la console du navigateur.</code></pre>
</span>
<form id="form-GETapi-v1-calendar--id--ics" data-method="GET"
      data-path="api/v1/calendar/{id}.ics"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-calendar--id--ics', this);">
    <h3>
        Requête&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-calendar--id--ics"
                    onclick="tryItOut('GETapi-v1-calendar--id--ics');">Essayer
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-calendar--id--ics"
                    onclick="cancelTryOut('GETapi-v1-calendar--id--ics');" hidden>Annuler
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-calendar--id--ics"
                    data-initial-text="Envoyer la requête"
                    data-loading-text="Envoi…"
                    hidden>Envoyer la requête
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/calendar/{id}.ics</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>En-têtes</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-calendar--id--ics"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-calendar--id--ics"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Paramètres d’adresse</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-v1-calendar--id--ics"
               value="1"
               data-component="url">
    <br>
<p>Identifiant de la date publiée. Example: <code>1</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Paramètres de requête</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>lang</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lang"                data-endpoint="GETapi-v1-calendar--id--ics"
               value="fr"
               data-component="query">
    <br>
<p>Langue des textes : fr (par défaut), en ou ar. Example: <code>fr</code></p>
            </div>
                </form>

                <h1 id="communaute">Communauté</h1>

    

                                <h2 id="communaute-GETapi-v1-demarches--slug--reviews">Avis sur une démarche</h2>

<p>
</p>

<p>Avis publiés après modération, avec la synthèse des prix et délais réellement constatés par les usagers.</p>

<span id="example-requests-GETapi-v1-demarches--slug--reviews">
<blockquote>Exemple de requête:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/demarches/passeport-biometrique/reviews" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/demarches/passeport-biometrique/reviews"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-demarches--slug--reviews">
            <blockquote>
            <p>Exemple de réponse (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
content-security-policy: default-src &#039;none&#039;; frame-ancestors &#039;none&#039;; base-uri &#039;none&#039;; form-action &#039;none&#039;
x-content-type-options: nosniff
x-frame-options: DENY
referrer-policy: strict-origin-when-cross-origin
permissions-policy: geolocation=(), microphone=(), camera=()
cross-origin-resource-policy: same-site
x-ratelimit-limit: 100
x-ratelimit-remaining: 99
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;summary&quot;: {
        &quot;count&quot;: 0,
        &quot;average_rating&quot;: 0,
        &quot;median_price&quot;: null,
        &quot;median_duration_days&quot;: null
    },
    &quot;data&quot;: []
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-demarches--slug--reviews" hidden>
    <blockquote>Réponse reçue<span
                id="execution-response-status-GETapi-v1-demarches--slug--reviews"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-demarches--slug--reviews"
      data-empty-response-text="<Réponse vide>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-demarches--slug--reviews" hidden>
    <blockquote>La requête a échoué:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-demarches--slug--reviews">

Vérifiez votre connexion. Les détails de l’erreur sont dans la console du navigateur.</code></pre>
</span>
<form id="form-GETapi-v1-demarches--slug--reviews" data-method="GET"
      data-path="api/v1/demarches/{slug}/reviews"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-demarches--slug--reviews', this);">
    <h3>
        Requête&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-demarches--slug--reviews"
                    onclick="tryItOut('GETapi-v1-demarches--slug--reviews');">Essayer
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-demarches--slug--reviews"
                    onclick="cancelTryOut('GETapi-v1-demarches--slug--reviews');" hidden>Annuler
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-demarches--slug--reviews"
                    data-initial-text="Envoyer la requête"
                    data-loading-text="Envoi…"
                    hidden>Envoyer la requête
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/demarches/{slug}/reviews</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>En-têtes</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-demarches--slug--reviews"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-demarches--slug--reviews"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Paramètres d’adresse</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>slug</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="slug"                data-endpoint="GETapi-v1-demarches--slug--reviews"
               value="passeport-biometrique"
               data-component="url">
    <br>
<p>Identifiant de la fiche. Example: <code>passeport-biometrique</code></p>
            </div>
                    </form>

                    <h2 id="communaute-GETapi-v1-questions">Questions et réponses</h2>

<p>
</p>

<p>Questions publiées et leurs réponses publiées (après modération).</p>

<span id="example-requests-GETapi-v1-questions">
<blockquote>Exemple de requête:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/questions?demarche=passeport-biometrique&amp;q=d%C3%A9lai&amp;per_page=20&amp;lang=fr" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/questions"
);

const params = {
    "demarche": "passeport-biometrique",
    "q": "délai",
    "per_page": "20",
    "lang": "fr",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-questions">
            <blockquote>
            <p>Exemple de réponse (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
content-security-policy: default-src &#039;none&#039;; frame-ancestors &#039;none&#039;; base-uri &#039;none&#039;; form-action &#039;none&#039;
x-content-type-options: nosniff
x-frame-options: DENY
referrer-policy: strict-origin-when-cross-origin
permissions-policy: geolocation=(), microphone=(), camera=()
cross-origin-resource-policy: same-site
x-ratelimit-limit: 100
x-ratelimit-remaining: 99
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [],
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;last_page&quot;: 1,
        &quot;total&quot;: 0
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-questions" hidden>
    <blockquote>Réponse reçue<span
                id="execution-response-status-GETapi-v1-questions"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-questions"
      data-empty-response-text="<Réponse vide>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-questions" hidden>
    <blockquote>La requête a échoué:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-questions">

Vérifiez votre connexion. Les détails de l’erreur sont dans la console du navigateur.</code></pre>
</span>
<form id="form-GETapi-v1-questions" data-method="GET"
      data-path="api/v1/questions"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-questions', this);">
    <h3>
        Requête&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-questions"
                    onclick="tryItOut('GETapi-v1-questions');">Essayer
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-questions"
                    onclick="cancelTryOut('GETapi-v1-questions');" hidden>Annuler
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-questions"
                    data-initial-text="Envoyer la requête"
                    data-loading-text="Envoi…"
                    hidden>Envoyer la requête
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/questions</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>En-têtes</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-questions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-questions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Paramètres de requête</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>demarche</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="demarche"                data-endpoint="GETapi-v1-questions"
               value="passeport-biometrique"
               data-component="query">
    <br>
<p>Slug d’une fiche : seulement ses questions. Example: <code>passeport-biometrique</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>q</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="q"                data-endpoint="GETapi-v1-questions"
               value="délai"
               data-component="query">
    <br>
<p>Mot-clé dans les questions. Example: <code>délai</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-questions"
               value="20"
               data-component="query">
    <br>
<p>Questions par page, de 1 à 50 (20 par défaut). Example: <code>20</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>lang</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lang"                data-endpoint="GETapi-v1-questions"
               value="fr"
               data-component="query">
    <br>
<p>Langue des textes : fr (par défaut), en ou ar. Example: <code>fr</code></p>
            </div>
                </form>

            

        
    </div>
    <div class="dark-box">
                    <div class="lang-selector">
                                                        <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                                        <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                            </div>
            </div>
</div>
</body>
</html>
