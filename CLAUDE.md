# MangaTrackr

Application web Symfony 6.4 de suivi de lecture de mangas (scanthèque, calendrier de sorties, export, admin EasyAdmin, connexion Google OAuth).

## Stack
- PHP >= 8.2, Symfony 6.4, Doctrine ORM 2 / MariaDB
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

## Bonnes pratiques
À respecter pour toute nouvelle fonctionnalité, et à tracer dans le récap final (« Bonnes pratiques appliquées », écarts justifiés).
- PSR : PSR-1 et PSR-4 (autoload `App\`), PSR-12 via `@Symfony` (PHP-CS-Fixer), PSR-3 (`LoggerInterface`), PSR-11 (conteneur), PSR-7/15 uniquement via HttpFoundation/HttpKernel.
- Symfony : contrôleurs minces (logique dans `src/Services/`), injection par constructeur + autowiring (jamais `$container->get()`), Voters pour l'autorisation, FormTypes + contraintes du Validator pour la validation, EventListener/Messenger pour le découplage, routes et mapping en attributs, configuration via paramètres/`.env`, Twig sans logique métier.
- PHP : `declare(strict_types=1)`, types de paramètres et de retour explicites, `final`/`readonly` quand pertinent.

## Règles
- Ne jamais modifier une migration déjà commitée : en générer une nouvelle avec `make sf-mm`.
- Avant de déclarer une tâche terminée : `make qa-php-stan` et `make test-only` doivent passer.
- Les secrets vont dans `.env.local` (non versionné), jamais dans `.env`.
- Branche de travail `dev`, PR vers `main`.

## Points d'attention
- Le shell exporte `APP_ENV=dev` : le Makefile préfixe PHPUnit par `APP_ENV=test` (hors Makefile : `APP_ENV=test vendor/bin/phpunit`).
- GrumPHP est actif : message de commit avec objet <= 60 caractères et lignes du corps <= 72, en français, préfixe `fix:`/`refactor:`/`feat:`/`test:`/`docs:`/`style:`.
- `strict_types` : les valeurs venant du front, des commandes ou des API sont souvent des chaînes ; les convertir à la frontière et les tester sous forme de chaînes (ADR 0001).
- Les tests doivent rester rejouables avec `make test-only` : ne pas laisser de données résiduelles.

## Contexte détaillé
Ces documents sont à tenir à jour. Ajouter un ADR (`docs/decisions/`) pour toute décision structurante.
- Contexte du projet (stack, architecture, environnement, workflow) :
@docs/claude/contexte.md
- État du projet et dette technique (daté) : `docs/claude/etat-et-dette-technique.md`
- Décisions d'architecture (ADR), avec un index et un modèle : `docs/decisions/README.md`
