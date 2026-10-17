# MangaTrackr

Application web Symfony 6.4 de suivi de lecture de mangas (scanthèque, calendrier de sorties, export, admin EasyAdmin, connexion Google OAuth).

## Stack
- PHP >= 8.1, Symfony 6.4, Doctrine ORM 2 / MariaDB
- Front : Twig + Symfony UX (Stimulus, Twig Components, Autocomplete), Webpack Encore, Tailwind
- API externes : Tenrai (remplace Jikan) via `src/Services/Api/ApiTenraiService.php`, MangaUpdates via `src/Services/Api/ApiMangaUpdatesService.php`

## Environnement
- Devcontainer (`.devcontainer/`) basé sur `docker-compose.yml` : service `app` (code dans `/var/www`) + `database` (MariaDB).
- Serveur : `symfony server:start -d --no-tls` (port 8000), Apache sur le port 8080.

## Commandes (Makefile)
- Tests : `make test-only` (PHPUnit seul), `make test` (réinitialise la BDD de test, charge les fixtures et lance PHPUnit)
- Qualité : `make qa-php-stan`, `make qa-php-cs-fixer`, `make qa-all`
- Symfony : `make sf-cc` (cache), `make sf-mm` (créer une migration), `make sf-dmm` (exécuter les migrations), `make sf-dfl` (fixtures)
- Assets : `make npm-dev`, `make npm-watch`, `make npm-build`

## Conventions
- `src/` → namespace `App\`, `tests/` → `App\Tests\`
- Style : PHP-CS-Fixer règles `@Symfony` (`.php-cs-fixer.dist.php`), appliqué automatiquement par un hook Claude après chaque modification de fichier PHP
- PHPStan niveau 6 sur `src/` et `tests/` (`phpstan.dist.neon`)
- Logique métier dans `src/Services/`, accès aux données dans `src/Repository/`, listeners Doctrine dans `src/EventListener/`
- Migrations Doctrine dans `migrations/`

## Règles
- Ne jamais modifier une migration déjà commitée : en générer une nouvelle avec `make sf-mm`.
- Avant de déclarer une tâche terminée : `make qa-php-stan` et `make test-only` doivent passer.
- Les secrets vont dans `.env.local` (non versionné), jamais dans `.env`.
- Branche de travail `dev`, PR vers `main`.
