# 019 — testeur — Recette fonctionnelle sur PostgreSQL avec les données réelles

- Tâche : T6
- Date : 2026-10-18
- Statut : partiel (recette verte, mais une écriture involontaire dans la base de dev, voir « Écarts »)

## Objectif
Recette de l'application sur `sf_mangatrackr_app` (PostgreSQL 18) chargée avec les données
réelles du rapport 017, en lecture seule, plus QA (PHPStan, PHPUnit, schéma, migrations,
séquences).

## Ce qui a été fait
- Serveur `symfony server:start -d --no-tls` (port 8000) pour les pages publiques et la recherche
  (curl) ; arrêté en fin de recette (port 8000 ne répond plus).
- Scripts jetables hors dépôt, dans le scratchpad de la session (`recette.php`, `recette2.php`,
  `http.php`, `q.php`) : noyau Symfony en `APP_ENV=dev`, avec `PGOPTIONS='-c
  default_transaction_read_only=on'` (toute écriture échoue côté PostgreSQL), cache et logs du noyau
  redirigés hors du dépôt. Connexion « simulée » par `KernelBrowser::loginUser` (services de session
  rendus publics par une passe de compilation locale au script), sans mot de passe ni hash.
- Aucun fichier du dépôt modifié (hors ce rapport et le journal). Aucune migration générée
  (`doctrine:migrations:diff` n'a rien créé). Utilisateurs anonymisés : A à F par ordre d'id ;
  A et C sont ROLE_ADMIN, B, D, E, F simples utilisateurs.

## Résultats

### Pages publiques et connexion (curl, serveur lancé)
| Scénario | Résultat |
|---|---|
| `/`, `/about`, `/terms_of_use`, `/login`, `/reset_password`, `/scantheque` (anonyme) | OK, 200 |
| `/catalog`, `?page=2`, `?page=464` (au-delà de la fin) | OK, 200 (page 464 vide) |
| `/calendar`, `?page=3` | OK, 200 |
| `/user`, `/admin_dashboard` anonyme | OK, 302 vers `/login` |
| Route inexistante | OK, 404 |

### Catalogue, pagination, tri (noyau, KnpPaginator)
| Scénario | Résultat |
|---|---|
| `paginationQuery` (7 073 mangas actifs non adultes sur 7 412), pages 1, 2, 443 (1 élément), 444 (vide) | OK, 16/16/1/0 éléments, total constant |
| Tri `title_slug ASC` page 1 = ordre strict de `sort()` PHP | OK (première valeur `000000-ultra-black`) ; aucune valeur NULL en base |
| `getTopMangas` (NULL de `mal_scored` en dernier par `CASE WHEN`), `getLatestMangas`, `getMangas`, `getTopMangas` avec `P30D` | OK, 4/4/16/4 résultats |
| Calendrier : `ORDER BY released_at DESC, m.title ASC`, pages 1 et 2 (22 966 sorties) | OK, ordre décroissant vérifié, 30 par page |
| `getBySlugTitleAndCategory`, `searchByTitles` (casse inversée) | OK |

### Recherche (`searchManga`, UNACCENT)
| Scénario | Résultat |
|---|---|
| 3 titres non ASCII réels (Σ, ☆, χ), recherche du titre exact | OK, 1 résultat chacun |
| 5 titres accentués réels : terme sans accent, puis terme en MAJUSCULES | OK ×10, titre retrouvé |
| Titre ASCII en majuscules ; terme absent ; `'; DROP--%_` | OK (résultat attendu, pas d'erreur SQL) |
| HTTP `POST /manga/search`, termes ASCII sans accent / minuscules sur titres accentués | OK, titre retrouvé (le test sur « Le Theatre de A » est OK ; un premier KO venait de mon script, un `â` non remplacé) |
| HTTP : terme absent, terme de 2 caractères, caractères spéciaux | OK, 200 |

Observation hors migration (comportement existant) : `MangaController::validateSearchTerm` impose
`^[a-zA-Z0-9-_\s]*$`. Un terme accentué ou contenant `:` est donc rejeté (« Aucun résultat »)
avant d'atteindre `searchManga` ; la recherche sans accent fonctionne donc, mais la saisie avec
accent n'est pas atteignable par l'interface.

### Entités à grosses relations (noyau)
| Scénario | Résultat |
|---|---|
| Chargement de 200 derniers `Manga`, `MangaStatistic`, `MangaTenraiAPI`, `MangaMangaUpdatesAPI`, `ReleaseMangaUpdatesAPI` ; toutes les `User`, `UserTrackList`, `MangaUserTrack`, `UserNews` | OK, comptes 7 412 / 7 415 / 5 679 / 2 038 / 22 966 / 6 / 6 / 68 / 6 |
| Manga le plus suivi : fantrads, éditeurs, types, statistique, Tenrai, MangaUpdates, statut | OK |
| 6 `UserTrackList` → tracks → manga et statut | OK |

### Authentification
| Scénario | Résultat |
|---|---|
| `UserLoader` : email exact, en majuscules, avec espaces autour et casse changée | OK, même identifiant |
| Email inconnu | OK, `null` |
| Doublons d'email à la casse près en base | 0 |
| Aucun hash lu ni modifié ; aucune réinitialisation | — |

### Compte connecté (6 comptes A à F, `loginUser`)
| Scénario | Résultat |
|---|---|
| `GET /user` et `GET /scantheque` pour A à F | OK, 200 ×12 |
| `GET /user/export/scantheque` pour A à F (service `ScanthequeExportService` + `SpreadSheetService`) | OK, 200 `text/csv` ; nombre de lignes = nombre de tracks + 1 en-tête (A : 0, B : 18, C : 38, D : 1, E : 2, F : 9) ; en-tête `Title;LastChapter;MaxChapter;Status` |
| Routes `/scantheque/*_datas`, chemins sans écriture : `smut` sans clé `mangas` → 400 ; statut inconnu → 404 ; `umut` sur son track sans `mut` → 400 ; `umut` et `dmut` sur le track d'un autre → 403 (voter) ; `umut` id inexistant → 404 | OK ×6 |
| `smut_datas` avec `{"mangas":[]}` (utilisateur connecté, puis anonyme) | KO, 500 (voir anomalie 2) |
| Chemins qui écrivent (`smut`/`umut`/`dmut` complets, `GET /manga/{slug}`) | Non testés en lecture seule (voir « Non vérifié ») |

### Admin EasyAdmin (A et C, ROLE_ADMIN réels, via `loginUser`)
| Scénario | Résultat |
|---|---|
| `/admin_dashboard` (redirection vers le CRUD Manga) pour A et C | OK, 200 après redirections |
| Index des 11 CRUD (Editor, Fantrad, Manga, MangaReleaseConfig, MangaStatus, MangaType, StatusTrack, TextContentPage, User, UserInvitationCode, UserResetPassword) | OK ×11, 200, lignes affichées (ex. Manga 21 `<tr>` = 20 lignes + en-tête) |
| Manga : page 2, page 3 triée `titleSlug DESC`, recherche `query=é`, détail id 1, formulaire d'édition (GET) id 1 | OK ×5 |
| Compte B (non admin) sur l'admin | OK, 403 |

### QA
| Contrôle | Résultat |
|---|---|
| `make qa-php-stan` | OK, `[OK] No errors` (181 fichiers) |
| `make test-only` | OK, **193 tests, 546 assertions** (référence rapport 017 : identique ; la référence du skill, 169/329, est antérieure) ; seul avis : dépréciation `ux-toggle-password` |
| `doctrine:schema:validate` (dev chargée) | OK, mapping et schéma synchronisés |
| `doctrine:schema:update --dump-sql` | OK, « Nothing to update » |
| `doctrine:migrations:diff` | OK, « No changes detected », aucun fichier créé (`migrations/` contient toujours 1 fichier) |
| `doctrine:migrations:up-to-date` | OK |
| 19 séquences : `last_value >= MAX(id)` | OK ×19, 0 KO. 16 appelées avec `last_value` = MAX(id) (ex. `manga` 7 428, `release_manga_updates_api` 22 966, `manga_manga_updates_api` 2 040) ; 3 tables vides (`manga_release_config`, `messenger_messages`, `user_reset_password`) : séquence non appelée, prochaine valeur 1 |

## Écarts, risques, points non vérifiés
1. **Écriture involontaire dans la base de dev (violation de la consigne).** J'ai lancé trois fois
   `GET /manga/le-theatre-de-a` sur le serveur démarré **sans** le mode lecture seule. Cette route
   déclenche `MangaStatisticSubscriber::incrementNbView` (un `flush`). Conséquence : pour la ligne
   `manga_statistic` id 332, `nb_view` est passé à 11 (valeur d'origine très probablement 8) et
   `updated_at` vaut `2026-10-18 15:43:45`. Pas d'autre écriture constatée : tous les scripts en
   noyau tournaient en lecture seule PostgreSQL, le reste des requêtes du serveur étant des GET sans
   effet de bord. La parité du rapport 017 (empreinte de `manga_statistic`) est donc rompue pour
   cette seule ligne. À décider par le chef : recharger la base via la procédure du rapport 017, ou
   accepter l'écart (donnée de compteur sans enjeu).
2. **Anomalie existante, sans lien avec PostgreSQL** : `MangaStatisticSubscriber::onScanthequeController`
   (ligne 79) lit `$requestParams['mangas'][0]['titleSlug']` sans vérifier que la liste n'est pas vide ;
   `POST /scantheque/smut_datas/{statut}` avec `{"mangas":[]}` produit donc un 500 (« Undefined array key 0 »,
   puis `TypeError` sur `incrementNbTrack(null)`). Le contrôleur prévoit pourtant ce cas (`count(...) > 0`).
   Pas de régression de la migration, mais défaut à confier au développeur si souhaité.
3. **Non vérifié** (écrit dans la base, interdit) : `smut_datas`/`umut_datas`/`dmut_datas` avec données
   valides, `GET /manga/{slug}` en lecture seule (le compteur `nb_view` est écrit), inscription, changement
   de mot de passe, suppression de compte, recherche avancée (appels aux API externes et écritures),
   connexion réelle par formulaire ou Google (pas de mot de passe), `INSERT` après le recalage des
   séquences (contrôlé par lecture de `last_value`, pas par insertion).
4. Les écrans EasyAdmin n'ont été vérifiés qu'en GET : pas de création, d'édition ni de suppression.
5. La simulation de connexion passe par `loginUser` avec un utilisateur chargé depuis la base ; le
   `UserLoader` est vérifié séparément (provider de session non rejoué sur `/login`).
6. Les pages sont vérifiées par code HTTP et nombre de lignes, pas visuellement (rendu, assets
   Encore, JavaScript non évalués).
7. Les logs de dev de la session ne montrent que des erreurs attendues (404 volontaires, absence de
   changement à `migrations:diff`) ; aucun message SQL PostgreSQL.

## Recommandation pour la suite
- Chef : trancher sur l'écart 1 (recharger la base dev ou l'accepter) avant la bascule ; le rejeu de
  `rejouer-transfert.sh` remet la base en l'état du rapport 017.
- Confier l'anomalie 2 au développeur (indépendante de la migration), à ranger dans la dette technique.
- Pour la bascule : refaire la même recette sur la base chargée avec le dump final, et ajouter un test
  automatisé couvrant l'export CSV et `searchManga` avec accents si l'on veut un filet permanent
  (jeu de données de test actuel pauvre en accents).
