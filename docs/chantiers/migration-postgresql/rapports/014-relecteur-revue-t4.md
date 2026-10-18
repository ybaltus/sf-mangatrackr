# 014 — relecteur — revue de la phase T4

- Tâche : T4 (revue)
- Date : 2026-10-18
- Statut : terminé

## Objectif
Relire le diff non commité (ci.yml, doctrine.yaml, MangaRepository, src/Doctrine/Query/UnaccentFunction, tests, PurgeTestDataTrait) et la cohérence avec les commits 8a83f44 et 45d43d3.

## Ce qui a été fait
Lecture du diff, des rapports 010-013, de ADR 0012, des entités liées à User. Aucune exécution (CI et Docker indisponibles) ; PHPStan/tests non relancés (le testeur 015 s'en charge).

## Résultats

Verdict : **validé avec réserves** (aucun bloquant).

### Bloquant
Aucun.

### Important
1. `.github/workflows/ci.yml` (env du job, `DB_PASSWORD`) : `DATABASE_URL` est composée sans encodage dans `.env.test`. Si le secret `DB_PASSWORD` contient `@ : / # ? % $`, l'URL est invalide, et la CI échoue à `migrate` avec une erreur de connexion peu lisible. Scénario : un secret généré avec des caractères spéciaux. Correction : documenter la contrainte (mot de passe alphanumérique) ou encoder avec `urlencode`/`rawurlencode` via `%env(urlencode:...)`/processeur, ou définir `DATABASE_URL` directement dans le job. Le repli `|| 'ChangeMe'` est acceptable (base éphémère) mais fait tourner un fork sans secret : OK.
2. Exécution CI non vérifiée (déjà déclarée par 011). Analyse statique du script : le heredoc `<<'SQL'` est correct (le scalaire YAML `run: |` retire l'indentation commune, terminateur `SQL` en colonne 0 après dédentation) ; `\gexec` placé sans `;` est valide ; `:'var'` s'interpole bien depuis stdin (pas avec `-c`, donc le choix est le bon) ; `format('%I/%L')` protège contre l'injection. `unaccent` est une extension « trusted » depuis PG13 : le propriétaire de la base non superutilisateur peut la créer, la migration baseline passera. Risque résiduel : `ICU_LOCALE 'fr'` avec `TEMPLATE template0` est valide en PG18. Recommandation : premier run via PR brouillon avant de se fier à la CI.

### Mineur
3. `ci.yml` : `PGPASSWORD: postgres-ci-superuser` en clair dans le workflow. Ce n'est pas un secret réel (service éphémère non exposé) mais un scanner de secrets peut le signaler ; acceptable, à justifier en commentaire (déjà partiellement fait). `DB_ROOT_PASSWORD` n'est plus référencé dans le workflow : le secret GitHub peut être supprimé (à noter dans l'état). L'étape `cp .env.test .env.test.local` est redondante.
4. `ci.yml` : `cs-fixer --dry-run` sur `src tests` est plus strict que l'ancien `fix src` : peut faire échouer la 1re CI si `main` diverge. Comportement voulu, mais à signaler dans la PR.
5. `src/Doctrine/Query/UnaccentFunction.php` : conforme (strict_types, `final`, types). Pas d'injection : le terme reste un paramètre lié, `StringPrimary` n'accepte que des expressions DQL. Réserve : `unaccent()` est `STABLE`, pas `IMMUTABLE` ; un index `unaccent(lower(title))` exige un wrapper IMMUTABLE (indiqué à juste titre en 012). De plus `LIKE '%terme%'` ne peut pas utiliser un btree même fonctionnel ; l'option réaliste serait `pg_trgm` + GIN. Aujourd'hui seq scan sur `manga` (54 lignes en test, volume prod à confirmer) : acceptable, à consigner en dette technique.
6. `MangaRepository::searchManga` : les jokers `%`/`_` saisis par l'utilisateur ne sont pas échappés (déjà noté, comportement antérieur inchangé). `searchByTitles` (dédoublonnage) reste sensible aux accents : écart assumé vs D4, à acter dans l'ADR ou la dette (décision fonctionnelle).
7. Portabilité : `UNACCENT` n'existe que sur PostgreSQL. Conforme à l'ADR 0012 (baseline refuse hors PostgreSQL), mais la recherche EasyAdmin (vendor) reste sensible aux accents : incohérence utilisateur entre front et admin, à noter dans `etat-et-dette-technique.md`.
8. `tests/Traits/PurgeTestDataTrait.php` : ordre FK correct (code d'invitation, qui porte la FK vers l'utilisateur, puis utilisateur ; cascade `remove` + orphanRemoval vers `UserTrackList`, `UserNews`, `UserResetPassword`, `MangaUserTrack`). Réserves : (a) `static::getContainer()` lève une exception si le noyau n'est pas démarré, mais le trait est gardé par `[] === $emails`, donc sans effet tant que l'email n'est enregistré qu'après démarrage ; (b) un test qui échoue avant `tearDown` laisse des lignes (`make test` les efface, déjà documenté) ; (c) le trait n'a pas de `declare` d'usage restreint (méthode `private` dans un trait : PHPStan OK). L'utilisateur est retrouvé par `strtolower(email)` : cohérent avec l'index unique `LOWER(email)`. `InvitationControllerTest` n'étant pas `final` (préexistant), pas de régression.
9. Convention : `tests/Controller/InvitationControllerTest` déclare le `tearDown()` avant les méthodes privées ; sans impact. Commentaires du trait en anglais, cohérents avec le reste.
10. Cohérence T4 : `.devcontainer/docker-compose.devcontainer.yml` conserve `DATABASE_URL_MARIADB`/`MARIADB_VERSION` et `docker-compose.yml` garde le service MariaDB (`MARIADB_ROOT_PASSWORD: root1234` en clair, préexistant) : cohérent avec D6 (conservé jusqu'à la bascule) mais à nettoyer en T5/T6. Le message du commit 8a83f44 est conforme à GrumPHP.

## Écarts, risques, points non vérifiés
- Aucun test ni CI exécuté par mes soins ; les chiffres 193 tests/546 assertions proviennent des rapports 011-013.
- Les 3 fichiers non suivis (`src/Doctrine/`, trait, rapports) et le diff ne sont pas commités : à inclure dans le commit (sinon CI cassée par la config `dql` sans classe).
- `etat.md` non modifié (rôle du chef).

## Recommandation pour la suite
Valider T4 avec réserves : traiter l'item 1 (documenter ou encoder), commiter en séparant `feat:` (UNACCENT), `test:` (purge), `fix:`/`refactor:` (CI), ouvrir la PR brouillon pour le premier run CI réel, et consigner en dette : index trigram/unaccent, recherche admin, `searchByTitles`, `dama/doctrine-test-bundle`.
