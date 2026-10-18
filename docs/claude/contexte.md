# Contexte du projet MangaTrackr

Document de référence pour démarrer une session Claude avec le contexte complet.
Il est importé par `CLAUDE.md` : il doit rester court. Le détail des choix est dans
[`docs/decisions/`](../decisions/README.md), l'état d'avancement dans
[`etat-et-dette-technique.md`](etat-et-dette-technique.md).

## Produit
Application web de suivi de lecture de mangas : scanthèque personnelle (en cours, en pause,
archivés), catalogue et recherche, calendrier des sorties, export CSV, administration
EasyAdmin. Inscription **sur invitation** (code envoyé par email), connexion par formulaire ou
Google OAuth.

## Stack
- PHP >= 8.2 (8.2 en local, en CI et en production), Symfony 6.4 (LTS), Doctrine ORM 2.20 / DBAL 3 / PostgreSQL 18 (ADR 0012 ; la production Hostinger est encore sur MariaDB, bascule à planifier).
- Front : Twig, Twig Components (`src/Twig/Components`), Stimulus (`assets/controllers`),
  Symfony UX (Autocomplete, Toggle Password), Webpack Encore, Tailwind (PostCSS).
- Admin : EasyAdmin 4.29. Pagination : KnpPaginator. Export : PhpSpreadsheet (CSV).
- API externes : Tenrai (`ApiTenraiService`) et MangaUpdates (`ApiMangaUpdatesService`),
  avec une base commune `AbstractApiService`. Tenrai remplace Jikan.
- Qualité : PHP-CS-Fixer (`@Symfony`), PHPStan niveau 6, PHPUnit 9.6, GrumPHP, PHP Insights.

## Architecture (où chercher quoi)
- `src/Controller/` : contrôleurs **minces** (formulaires, flash, redirections). `Admin/` pour
  EasyAdmin, `Auth/` pour l'invitation et la connexion.
- `src/Services/` : logique métier. `Api/` (API externes), `Common/` (mail, tableur,
  scanthèque), `User/` (compte, invitation, réinitialisation, export), `Command/`.
- `src/Repository/` : accès aux données. `src/Entity/` : modèle (manga et référentiels,
  suivi utilisateur `UserTrackList` / `MangaUserTrack`, invitations et réinitialisations,
  tables miroir des API, statistiques).
- `src/EventListener/` (listeners Doctrine : slugs, hash du mot de passe) et
  `src/EventSubscriber/` (EasyAdmin : code d'invitation + email ; statistiques de manga).
- Sécurité : deux authenticators (`Security/Auth/AppAuthenticator`, `Security/OAuth/GoogleAuthenticator`),
  `access_control` : `/admin` = `ROLE_ADMIN`, `/user` = `ROLE_USER`. Voter
  `MangaUserTrackVoter` (`mut_edit`, `mut_delete`) pour les routes de la scanthèque.
- Front / back : la scanthèque est synchronisée avec le `localStorage` via
  `assets/js/user-service.js` qui appelle `/scantheque/*_datas`. **Le front envoie les nombres
  sous forme de chaînes** (valeurs d'`<input>`), voir l'ADR 0001.

## Environnement
- Devcontainer (`.devcontainer/`) sur `docker-compose.yml` : service `app` (code dans
  `/var/www`, Apache :8080, extension `pdo_pgsql`) et `postgres` (PostgreSQL 18, hôte `postgres`,
  volume `pg-data`). Le service `database` (MariaDB) est conservé seulement comme source
  d'import jusqu'à la bascule, avec la base `sf_mangatrackr_app_import`.
- **Le shell exporte `APP_ENV=dev`** : il écrase `phpunit.xml.dist`. Le Makefile préfixe donc
  PHPUnit par `APP_ENV=test`. Hors Makefile, lancer `APP_ENV=test vendor/bin/phpunit`.
- Base PostgreSQL : cluster en UTF8 avec collation ICU `fr` ; extension `unaccent` créée par la
  baseline (recherche insensible aux accents) ; rôle applicatif non superutilisateur avec
  `CREATEDB` (nécessaire à `make test`). Base de test `<base>_test` (suffixe ajouté par
  `doctrine.yaml`) : créée par `docker/postgres/init/01-create-test-database.sh` à la première
  création du volume `pg-data`, et par le workflow CI (service `postgres:18`, rôle créé par `psql`).
  `DATABASE_URL` est composée dans `.env` ; un `%` dans le mot de passe doit être doublé.
- Outils de transfert MariaDB vers PostgreSQL et de contrôle de parité : skill
  `.claude/skills/verif-donnees/` (inventaire, transfert, rejeu), avec garde-fou sur la cible
  (`CONFIRM_TARGET`).
- Secrets : `.env.local` (non versionné) ; la config Claude interdit sa lecture.

## Commandes utiles
Voir `CLAUDE.md` (liste du Makefile). À retenir : `make test` réinitialise la base de test,
charge les fixtures et lance PHPUnit ; `make test-only` rejoue sans réinitialiser (les tests
doivent donc rester rejouables) ; `make qa-php-cs-fixer` couvre `src` et `tests`.

## Workflow Git
Branche de travail `dev`, PR vers `main`. La CI GitHub (PHP 8.2) ne tourne que sur les
PR vers `main`. GrumPHP vérifie à chaque commit : cs-fixer, PHPStan, `symfony security:check`
et le message (**objet <= 60 caractères, lignes du corps <= 72**). Commits en français,
préfixes `fix:`, `refactor:`, `feat:`, `test:`, `docs:`, `style:`.

## Configuration Claude du dépôt
`.claude/settings.json` (versionné) : autorise `make`, `php bin/console`, PHPStan,
PHP-CS-Fixer et la lecture de `docker-compose.yml` ; interdit la lecture de `.env.local` et
les `doctrine:*:drop` ; un hook lance PHP-CS-Fixer après chaque modification de fichier PHP.
