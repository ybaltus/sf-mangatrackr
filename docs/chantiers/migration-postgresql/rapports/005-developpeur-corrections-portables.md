# 005 — developpeur — Corrections de code portables MariaDB / PostgreSQL

- Tâche : T4.7
- Date : 2026-10-18
- Statut : terminé

## Objectif
Appliquer les corrections de code portables de l'audit (rapport 002, constats #10, #11, #12,
#13, #14, #15, #16, #23) : même comportement sur MariaDB aujourd'hui et sur PostgreSQL demain,
avec des tests de non-régression. Hors périmètre : accents (T4.8), mapping de la table `user`
(T4.3), configuration Doctrine, Docker, migrations.

## Ce qui a été fait

### Constats traités
| # | Correction | Fichier |
|---|---|---|
| 10 | `getTopMangas` : `CASE WHEN a.malScored IS NULL THEN 1 ELSE 0 END AS HIDDEN malScoredIsNull`, tri `malScoredIsNull ASC` puis `a.malScored DESC` (NULL en dernier, comme MariaDB) | `src/Repository/MangaRepository.php` |
| 11 | `getLatestMangas` : même technique sur `m.publishedAt` (`publishedAtIsNull`) | idem |
| 12 | `searchManga` : `LOWER(m.title) LIKE LOWER(:searchTerm)` | idem |
| 13 | `searchByTitles` : `LOWER(m.titleSynonym) = LOWER(:title) OR LOWER(m.titleEnglish) = LOWER(:title)` + `orderBy('m.id', 'ASC')` (le `[0]` de `ApiMangaUpdatesService::verifyIfExistWithOtherTitles` devient déterministe) | idem |
| 14 | `getBySlugTitleAndCategory` : `orderBy('m.id', 'ASC')` avant `setMaxResults(1)` | idem |
| 15 | Lecture insensible à la casse : `UserRepository::findOneByEmailInsensitive()` (`LOWER(u.email) = LOWER(:email)`, entrée normalisée) ; `UserRepository` implémente `UserLoaderInterface` (`loadUserByIdentifier`) et le provider `app_user_provider` n'a plus de `property: email` → connexion par formulaire et remember-me passent par cette recherche. Appelants : reset (`ResetPasswordController`), OAuth (`GoogleAuthenticator::getUserFromRessourceOwner`), `app:create-user` (contrôle de doublon) | `src/Repository/UserRepository.php`, `config/packages/security.yaml`, `src/Controller/ResetPasswordController.php`, `src/Security/OAuth/GoogleAuthenticator.php`, `src/Command/CreateUserCommand.php` |
| 15/16 | Écriture normalisée : `User::setEmail()` et `UserInvitationCode::setEmail()` stockent `EmailNormalizer::normalize()` (`mb_strtolower(trim())`). Couvre inscription par invitation (formulaire), EasyAdmin (création d'invitation et d'utilisateur), OAuth, commande et fixtures, puisque tous passent par le setter. L'unicité devient de fait insensible à la casse (`UniqueEntity('email')` compare la valeur normalisée) | `src/Services/Common/EmailNormalizer.php` (nouveau), `src/Entity/User.php`, `src/Entity/UserInvitationCode.php` |
| 23 | `#[ORM\OrderBy(['id' => 'ASC'])]` sur `User::$userResetPasswords` et `UserTrackList::$mangaUserTracks` (aucun impact schéma) | `src/Entity/User.php`, `src/Entity/UserTrackList.php` |

Effet de bord voulu : dans `InvitationController`, la comparaison `$userInvitationCode->getEmail() !== $user->getEmail()`
se fait désormais entre deux valeurs normalisées (une saisie « Toto@… » sur une invitation « toto@… » est acceptée).

### Tests ajoutés (16)
- `tests/Repository/MangaRepositoryTest.php` (6) : NULL en dernier pour le top (liste complète
  + `getTopMangas(1)` qui doit renvoyer un manga noté alors que toutes les fixtures sont sans note),
  NULL en dernier pour les dernières sorties, recherche catalogue insensible à la casse
  (terme en chaîne, ADR 0001), `searchByTitles` égalité insensible à la casse (et pas de
  correspondance partielle), tri par id. Mangas créés avec un identifiant aléatoire et
  supprimés en `tearDown` (vérifié : 0 ligne résiduelle après deux exécutions).
- `tests/Repository/UserRepositoryTest.php` (3) : recherche par email en majuscules/espaces,
  email inconnu, `loadUserByIdentifier`.
- `tests/Services/Common/EmailNormalizerTest.php` (4) : normalisation (dont multioctet) et
  setters des deux entités.
- `SecurityControllerTest::testLoginIsCaseInsensitiveOnEmail`, `ResetPasswordControllerTest::testResetPasswordFormSubmitIsCaseInsensitiveOnEmail`,
  `CreateUserCommandTest::testEmailAlreadyExistsIgnoresCase` (fixtures uniquement, pas de création).

### Commandes
`make qa-php-cs-fixer` (3 imports réordonnés), `make qa-php-stan`, `make test-only` (deux fois),
`php bin/console doctrine:schema:validate`, comptages SQL agrégés via `dbal:run-sql` (dev et test).

## Résultats
- `make qa-php-stan` : `[OK] No errors` (176 fichiers).
- `make test-only` : `OK (185 tests, 525 assertions)`, deux exécutions consécutives identiques
  (référentiel 169 / 329 : +16 tests, aucun test supprimé). Les tests réseau
  (`ApiTenraiCommandTest`, `InitDataCommandTest`, `ApiMangaUpdatesCommandTest`) sont passés lors
  de ces exécutions ; ils restent non déterministes.
- `doctrine:schema:validate` : mapping OK ; base de dev désynchronisée uniquement sur les écarts
  d'index connus (`manga_tenrai_api`, `messenger_messages`, constat #27), sans lien avec cette tâche.
- Emails existants (comptages uniquement) :

| Base | Lignes `user` | Non normalisés | Doublons insensibles à la casse | Lignes `user_invitation_code` | Non normalisés | Doublons |
|---|---|---|---|---|---|---|
| dev | 10 | 0 | 0 | 0 | 0 | 0 |
| test | — | 0 | — | — | — | — |
| dump de prod (audit 002) | — | 0 | 0 | — | 0 | 0 |

  Aucune migration de données nécessaire ; rien n'a été modifié en base.

## Écarts, risques, points non vérifiés
- **Non vérifié sur PostgreSQL** (pas de serveur ni de `pdo_pgsql`) : les tests passent sur
  MariaDB, où l'ancien code donnait déjà ce résultat ; ils ne deviendront discriminants qu'en
  phase 5 sur PG. Le DQL utilisé (`CASE`, `HIDDEN`, `LOWER`) est standard et traduit à
  l'identique par DBAL pour les deux plateformes.
- `#14` : `titleSlug` est **unique** dans le mapping, donc la requête ne peut renvoyer qu'une
  ligne ; le tri ajouté est défensif, sans changement fonctionnel (pas de test dédié).
- `#13` : passer de `LIKE` sans joker à `=` change deux cas marginaux sur MariaDB : un `%`/`_`
  dans un titre n'agit plus comme joker (plus correct), et `=` ignore les espaces finaux
  (collation PAD SPACE) alors que `LIKE` non. Impact négligeable sur le dédoublonnage.
- `LOWER(u.email)` empêche l'usage de l'index unique `email` pour la recherche : sans effet
  à cette volumétrie (10 utilisateurs). L'unicité en base reste sensible à la casse sur PG ;
  elle est garantie par la normalisation applicative, pas par un index `LOWER(email)` (option
  du constat #16, non faite : nécessite du SQL de migration, hors périmètre).
- Le provider de sécurité change (`property` retirée au profit de `UserLoaderInterface`) :
  `refreshUser` continue de recharger par id ; testé par les tests de connexion existants.
- `trim()` est inclus dans la normalisation (le formulaire Symfony trime déjà par défaut).

## Recommandation pour la suite
- **Points d'insertion T4.8 (insensibilité aux accents, PG)** :
  - `src/Repository/MangaRepository.php`, `searchManga()` : la condition
    `LOWER(m.title) LIKE LOWER(:searchTerm)` (une fonction DQL `UNACCENT` ou une collation ICU
    non déterministe s'insère ici).
  - `src/Repository/MangaRepository.php`, `searchByTitles()` : la condition
    `LOWER(m.titleSynonym) = LOWER(:title) OR LOWER(m.titleEnglish) = LOWER(:title)`
    (dédoublonnage MangaUpdates ; MariaDB est aujourd'hui insensible aux accents ici aussi).
  - Recherche EasyAdmin (`LOWER(...) LIKE` côté vendor) : ne passe pas par nos repositories,
    nécessite une surcharge du `createIndexQueryBuilder` des CRUD si l'insensibilité aux accents y est voulue.
  - Emails : non concernés (pas d'accents attendus ; la normalisation reste `mb_strtolower`).
- Hors périmètre, relevés au passage :
  - `GoogleAuthenticator` : le contrôle `in_array($resourceOwner->getEmail(), explode(',', $this->oauthTestEmails))`
    est sensible à la casse et aux espaces (indépendant de la base) ; le normaliser aussi.
  - `AppAuthenticator::authenticate` passe `$request->request->get('email', '')` (type mixte)
    à `UserBadge` : utiliser `getString()` (ADR 0001).
  - `CreateUserCommandTest::testSuccessfulExecution` laisse un utilisateur `test_<n>@test.fr`
    en base de test (dette ADR 0007 préexistante).
  - Option D4/#16 : index unique sur `LOWER(email)` dans la baseline PG (T4.4) pour garantir
    l'unicité au niveau base.
