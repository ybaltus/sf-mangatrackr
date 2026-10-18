# 006 — relecteur — Revue de T4.7 (corrections portables MariaDB / PostgreSQL)

- Tâche : T4.7
- Date : 2026-10-18
- Statut : terminé
- Verdict : **validé avec réserves** (aucun bloquant, aucun majeur, 4 mineurs)

## Objectif
Relire les modifications de code de T4.7 (rapport 005, constats #10-#16 et #23 du rapport 002) :
exactitude, régressions, conventions, sécurité de l'authentification après le retrait de
`property: email` dans le provider.

Périmètre relu : `git diff -- src config tests` + `src/Services/Common/EmailNormalizer.php`,
`tests/Repository/MangaRepositoryTest.php`, `tests/Repository/UserRepositoryTest.php`,
`tests/Services/Common/EmailNormalizerTest.php`.

## Ce qui a été fait
- Lecture du diff et des fichiers non suivis ; lecture de `AppAuthenticator`, `GoogleAuthenticator`,
  `InvitationController`, `ResetPasswordService`, `HomeController`, fixtures, mapping `Manga`.
- Lecture du code Symfony vendor : `LoginThrottlingFactory`, `DefaultLoginRateLimiter`
  (clé de throttling), comportement d'`EntityUserProvider` sans `property`.
- `make qa-php-stan` : `[OK] No errors` (176 fichiers).
- `make test-only` : `OK (185 tests, 525 assertions)`, exécuté deux fois (après PHPStan puis seul).
  Les dépréciations affichées dans `MangaRepositoryTest::setUp` sont préexistantes
  (détection de version DBAL, ux-toggle-password), sans lien avec T4.7.
- Rejouabilité : comptage en base de test après exécution, 0 manga résiduel créé par
  `MangaRepositoryTest`, 0 `manga_tenrai_api` orphelin.
- Aucun fichier de code modifié.

## Résultats

### Point 1 — Authentification (provider sans `property`)
| Usage | Constat |
|---|---|
| Form login (`AppAuthenticator`) | `UserBadge($email)` sans loader explicite → provider → `EntityUserProvider::loadUserByIdentifier` délègue à `UserRepository::loadUserByIdentifier` (interface `UserLoaderInterface` implémentée). OK, couvert par `testLoginIsCaseInsensitiveOnEmail`. |
| Refresh en session | `EntityUserProvider::refreshUser` recharge par identifiant Doctrine (id), indépendant de `property`. `hasUserChanged` compare `getUserIdentifier()` : valeur hydratée par Doctrine (pas de setter), identique avant/après. Pas de déconnexion des sessions existantes (0 email non normalisé en dev et dans le dump). OK. |
| Remember-me (signature) | Cookie signé sur `getUserIdentifier()` (email normalisé), relu via le provider → `loadUserByIdentifier`. Cookies existants : identifiants déjà en minuscules. OK. |
| Google OAuth | `findOneByEmailInsensitive` ; création via `setEmail` normalisé. Liaison d'un compte local `a@x` à un Google `A@x` : comportement identique à MariaDB actuel (collation `_ci`), pas une régression. OK. |
| Reset de mot de passe | `findOneByEmailInsensitive` ; pas d'énumération nouvelle (même réponse/redirect qu'avant). OK. |
| switch_user | Commenté dans `security.yaml` : non concerné. |
| Autres providers | Aucun. Aucun appel résiduel à `findOneByEmail` dans `src/` (seulement dans des tests, sur des emails déjà en minuscules). |
| Prise de compte / collision de casse | Inscription : invitation + `UniqueEntity('email')` sur valeur normalisée → impossible de créer `VICTIM@x` à côté de `victim@x`. Pas de vecteur nouveau. |

### Point 2 — Normalisation dans les setters
- EasyAdmin passe par le PropertyAccessor → setter : normalisé. Fixtures et commande : idem.
- `UniqueEntity('email')` : la validation s'exécute après le mapping du formulaire, donc sur la
  valeur normalisée ; unicité de fait insensible à la casse. Sur PG, une ligne historique en
  casse mixte échapperait au contrôle, mais il n'y en a aucune (comptages du rapport 005) et
  l'index `LOWER(email)` est décidé pour la baseline T4.4.
- Listener de hash : ne lit pas l'email, non impacté.
- `InvitationController:50` : comparaison entre deux valeurs normalisées (effet voulu, documenté).

### Point 3 — Tri des NULL (`CASE … AS HIDDEN`)
- Équivalent exact au comportement MariaDB (NULL en dernier en `DESC`) ; DQL standard, l'alias
  scalaire `sclr_N` en `ORDER BY` est accepté par MariaDB et PostgreSQL.
- `getTopMangas` / `getLatestMangas` ne sont **pas** paginés (appel direct dans `HomeController`,
  `setMaxResults` simple, jointure to-one) : aucune interaction avec KnpPaginator / COUNT.
  `CatalogController` et `CalendarController` paginent d'autres requêtes, non modifiées.

### Point 4 — `LOWER()`
- Portable (fonction DQL standard, paramètre typé texte sur PG). Perte d'usage de l'index
  unique `email` et d'un éventuel index sur `title` : négligeable à la volumétrie actuelle
  (10 utilisateurs), à reconsidérer si l'index `LOWER(email)` de T4.4 est créé (il sera alors
  utilisable par `LOWER(u.email) = LOWER(:email)` sur PG).

### Point 5 — Tests
- Discriminants sur PG avec l'ancien code : oui pour top/latest (NULL en tête → assertions
  `results[0]` échouent), recherche (LIKE sensible à la casse), `searchByTitles`, login, reset,
  commande. Non discriminants sur MariaDB (reconnu dans le rapport 005).
- Rejouables (ADR 0007) : identifiants aléatoires, suppression en `tearDown` (cascade remove sur
  `mangaTenraiAPI`), 0 ligne résiduelle vérifiée.
- ADR 0001 : terme de recherche et emails passés en chaînes ; commande testée avec arguments chaînes.

### Constats
| # | Gravité | Fichier:ligne | Scénario | Correction suggérée |
|---|---|---|---|---|
| R1 | mineur | `src/Security/Auth/AppAuthenticator.php:35-40` (interaction avec `src/Services/Common/EmailNormalizer.php:18`) | Le `trim()` de la normalisation fait que `" victim@x"`, `"  victim@x"`, … désignent désormais le même compte, alors que la clé de throttling Symfony (`DefaultLoginRateLimiter:70-73`) ne fait que `mb_strtolower` de l'identifiant brut. Un attaquant obtient un seau de 5 essais par variante d'espaces en tête, plafonné par le limiteur IP (5 × 5 = 25 essais / 15 min / IP) au lieu de 5 pour un compte. Les espaces finaux contournaient déjà le seau sous MariaDB (PAD SPACE) ; les espaces en tête sont nouveaux. | Normaliser l'identifiant avant le badge : `new UserBadge(EmailNormalizer::normalize($request->getPayload()->getString('email')))` (règle aussi le `get()` à type mixte relevé dans le rapport 005, ADR 0001). Test : 6 tentatives avec variantes d'espaces → throttling. |
| R2 | mineur | `src/Repository/MangaRepository.php:38-39` et `:67-68` | Pas de critère de départage : à score (`malScored`) ou date (`publishedAt`) égaux, l'ordre — et donc les 4 mangas affichés en accueil — dépend du moteur et du plan d'exécution ; il pourra différer entre MariaDB et PG (même famille que #14/#23). Les fixtures ont toutes la même date de création à la seconde près. | Ajouter `->addOrderBy('m.id', 'DESC')` (ou `ASC`) en dernier critère. |
| R3 | mineur | `tests/Repository/MangaRepositoryTest.php:120-124` | `testLatestMangasPutNullPublicationDatesLast` suppose qu'aucun manga en base de test n'a une date > +1 jour. Vrai avec les fixtures actuelles, mais les tests réseau (`ApiTenraiCommandTest`, `InitDataCommandTest`) peuvent insérer des mangas réels ; une date de publication future annoncée par l'API ferait échouer le test. | Utiliser une date très lointaine (`+100 years`) ou ne vérifier que l'ordre relatif daté/non daté. |
| R4 | mineur (info) | `src/Repository/UserRepository.php:53`, `src/Repository/MangaRepository.php:119,142` | `LOWER()` sur la colonne empêche l'usage des index existants (MariaDB et PG). Sans effet à cette volumétrie. | Rien à faire maintenant ; sur PG, l'index unique `LOWER(email)` prévu en T4.4 couvrira la recherche d'email. |

Aucun test supprimé ni affaibli ; aucun secret ni donnée de dump ; aucune migration modifiée ;
`OrderBy` sur collections sans impact schéma ; `strict_types` présent dans les nouveaux fichiers.

## Écarts, risques, points non vérifiés
- **Non vérifié sur PostgreSQL** (pas de serveur ni `pdo_pgsql` dans cette phase) : le caractère
  discriminant des tests est raisonné, pas constaté. À rejouer en phase 5.
- OAuth Google non testé de bout en bout (pas de test fonctionnel existant) ; revue par lecture.
- `GoogleAuthenticator:36` (`in_array` sensible à la casse sur `oauthTestEmails`) : préexistant,
  hors périmètre, déjà relevé par le rapport 005.

## Recommandation pour la suite
- Corriger R1 et R2 (quelques lignes, faible risque) avant ou pendant T4.8 ; R3 au passage.
- Rejouer `tests/Repository` et les tests d'authentification sur PG en phase 5 pour confirmer
  qu'ils échoueraient sans les corrections.
