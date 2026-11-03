# Vérification du cahier des charges DemarchesTchad v2.0

Date de vérification : 3 novembre 2026. Document source : `DemarchesTchad.pdf` (74 pages).

## 1. Ce que contient réellement le PDF

- Pages 1 à 2 : page de garde et avant-propos.
- Pages 3 à 70 : 80 fiches détaillées de démarches (pièces, règles, étapes, coût, délai, validité).
- Pages 71 à 72 : règles transversales (8 règles générales, règles pour mineurs et étrangers).
- Pages 73 à 74 : auto-contrôle « 15/15 critères validés » et liste de 20 démarches pour la phase 2.

**Écart avec le sommaire** : le sommaire annonce des chapitres 1 à 5 (contexte, architecture, modules) et 16 à 18 (API, déploiement). Ils ne figurent pas dans le PDF. L'architecture ne peut donc pas être vérifiée contre le document : elle l'a été contre la pile annoncée (Laravel 11, Next.js 14, MySQL 8 — la base tourne en MySQL 8.0.45).

## 2. Contenu importé et publié

| Contrôle | Résultat |
|---|---|
| Fiches du cahier | 80 |
| Fiches publiées en base | 80 (rapprochées par titre : 80/80) |
| Catégories | 10, réparties comme dans le cahier |
| Valeurs contrôlées dans le texte du PDF | 1 592 ; 30 non retrouvées à cause de la mise en page (colonnes mêlées) |
| Fiches avec pièces, étapes, coût et délai | 80/80 (test `CahierConformityTest`) |
| Règles transversales | Présentes sur la page `/regles` |

## 3. Écarts volontaires avec le cahier (corrections sourcées par la presse)

| Démarche | Cahier | Base | Source de la correction |
|---|---|---|---|
| Carte nationale d'identité — organisme | Délégation de la Sécurité publique | ANATS | Article de presse (Tchadinfos, 2023) |
| Passeport — coût | 50 000 à 85 000 FCFA selon urgence | 60 000 FCFA | Article de presse (Alwihda) |
| Passeport — organisme | DGDI, N'Djaména | ANATS | Article de presse |

Ces corrections sont conservées : elles viennent de sources datées et citées. Elles restent non vérifiées auprès de l'administration, et le cahier les contredit.

## 4. Contradictions du cahier avec nos règles de validité

| Document | Cahier | Notre règle | Décision |
|---|---|---|---|
| CNI | 10 ans | 10 ans (source secondaire 2018) ; exigence métier 5 ans | À confirmer auprès de l'ANATS |
| Permis de conduire | 10 ans | 4 ans (source secondaire PRADO, 2018) | À confirmer |
| Passeport ordinaire | 5 ans | Aucune durée sourcée | Non tranché : pas de source officielle |
| Acte de naissance | « Généralement 3 mois » | Permanent (exigence de l'équipe) | Exigence de l'équipe retenue ; le cahier n'est pas sourcé |
| Acte de décès | Permanent | Permanent | Concordant |
| Acte de mariage | Permanent | Lié au statut matrimonial | Exigence de l'équipe retenue |
| Casier judiciaire | 3 mois | 3 mois calendaires | Concordant |

Les durées du cahier n'ont pas de source officielle citée. Elles ne sont donc pas affichées comme des règles confirmées.

## 5. Affirmations de l'auto-contrôle du cahier : vérifiées et fausses

| Critère annoncé « OK » | Constat réel |
|---|---|
| Sources identifiées par ministère | 78 fiches sur 80 n'ont aucune source officielle |
| Date de vérification sur chaque fiche | 80 fiches sur 80 n'ont aucune date de vérification |
| 4 à 8 règles de gestion par démarche | 10 fiches n'en ont que 1 à 3 (ex. renouvellement de la CNI : 3 règles dans le PDF lui-même) |
| Données réelles issues des administrations | Non démontrable : le cahier ne cite aucun document officiel |

Ces trois points ne peuvent pas être réglés sans consulter les administrations. Ils restent ouverts. Les fiches affichent déjà « information non vérifiée » tant qu'aucune date de vérification n'existe.

## 6. Ce qui n'est pas implémenté

- **Phase 2** : le cahier annonce « 20 démarches restantes », mais sa liste en fin de document n'en contient que **12**. Elles sont créées en **brouillon** (non publiées, non visibles des citoyens) avec les sources trouvées et une date de consultation (03/11/2026). Constat par source : la plupart des démarches n'ont pas de texte officiel vérifiable ; l'adoption internationale est suspendue selon le ministère français des Affaires étrangères ; la concession minière fait l'objet d'une suspension signalée par la presse (arrêté n°064 du 10 septembre 2026).
- **Validité du cahier sur les fiches** : le tableau « coût / délai / validité » du cahier n'est pas affiché. Les validités sont gérées par la table des règles de validité, avec leur source. Afficher les valeurs du cahier serait contradictoire (voir section 4).
- **Chapitres 1 à 5 et 16 à 18** : absents du PDF.

## 7. Actions réalisées pendant cette vérification

- Test `CahierConformityTest` (4 tests) : 80 fiches publiées, 10 catégories, pièces et étapes présentes, 12 brouillons de phase 2 non publiés.
- Création des 12 brouillons de phase 2 (`CahierPhase2DraftsSeeder`), avec date de consultation.
- Recherches de sources pour la phase 2 (12 sujets, 03/11/2026).
- Ce rapport.

## 8. Propositions hors cahier : sources (consultées le 03/11/2026)

| Proposition | Statut | Constat |
|---|---|---|
| Certificat de célibat | partiel | Guide de l'ambassade de Suisse : certificat de moins de 6 mois délivré par la mairie, ou fiche individuelle d'état civil de l'office de N'Djaména. Source étrangère. |
| Déclaration de changement d'adresse | partiel | L'ANATS (ordonnance du 29/03/2016) met à jour les informations d'identité ; procédure non trouvée. |
| Copie ou extrait de jugement | partiel | Seule la délivrance des jugements supplétifs de naissance (suspendue puis rétablie) est documentée. |
| Attestation de régularité fiscale | non trouvé | Aucune source tchadienne. |
| Certificat de vie (pensionnés) | non trouvé | Aucune source tchadienne ; la CNPS compte près de 11 000 retraités. |
| Duplicata de carte grise | non trouvé | Aucune source tchadienne (résultats français). |
| Mutation de véhicule | non trouvé | Aucune source tchadienne. |
| Acte de notoriété | non trouvé | Aucune source tchadienne. |

Les 8 sont créées en brouillon (`CahierProposalsDraftsSeeder`), non publiées, avec leur statut et leurs sources.

## 9. Règles manquantes non ajoutées

Les 10 fiches qui n'ont que 1 à 3 règles ne peuvent pas recevoir de règles supplémentaires : le cahier n'en fournit pas, et les recherches n'ont trouvé aucun texte officiel (renouvellement de passeport, CNI, permis, etc.). Ajouter des règles sans source serait inventer des obligations pour les citoyens. Il faut consulter la DGDI, l'ANATS, la Direction des transports et les services concernés.

Aucune donnée de démarche n'a été modifiée.
