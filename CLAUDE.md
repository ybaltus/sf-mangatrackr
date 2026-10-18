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

## Méthode de travail
- **Git** : ne commiter ni pousser que sur demande explicite, et ne pas se fier à un accord donné pour une étape précédente. Quand plusieurs commits sont demandés, découper par sujet (le `fix:` d'abord, le style séparé de la logique) en gardant un arbre cohérent et vérifié (cs-fixer, PHPStan) à chaque étape ; GrumPHP contrôle l'arbre entier, pas l'index.
- **Actions visibles par d'autres** (PR, push, modification d'une PR) : demander confirmation avant ; ouvrir la PR d'abord en brouillon.
- **Audits et mises à jour** : commencer en lecture seule et présenter l'ampleur ; appliquer ensuite sans commiter pour laisser relire le diff ; garder une sauvegarde pour pouvoir revenir en arrière.
- **Dépendances** : Composer — vérifier que le lock s'installe avec le PHP minimum de `composer.json` (ADR 0008) ; npm — modifier le lockfile avec npm 11, valider avec `npm ci` en npm 10.8.2 puis `npm run build` (ADR 0009 et 0010).
- **Rapport** : dire franchement les échecs, les régressions et ce qui n'a pas pu être vérifié ; ne rien déclarer terminé sans `make qa-php-stan` et `make test-only` verts.
- **Traçabilité** : un ADR pour toute décision structurante, et mise à jour de `docs/claude/etat-et-dette-technique.md` en fin de chantier.

## Chantiers (travaux en plusieurs sessions)
- La session principale pilote en chef de projet et délègue aux sous-agents de `.claude/agents/` (ADR 0011, skill `chantier`).
- État sur disque dans `docs/chantiers/<nom>/` (`etat.md` = tableau de bord) ; index : `docs/chantiers/README.md`.
- En cours : **migration-postgresql** (MariaDB → PostgreSQL 18). Reprendre avec `/chantier reprendre migration-postgresql`.

## Contexte détaillé
Ces documents sont à tenir à jour. Ajouter un ADR (`docs/decisions/`) pour toute décision structurante.
- Contexte du projet (stack, architecture, environnement, workflow) :
@docs/claude/contexte.md
- État du projet et dette technique (daté) : `docs/claude/etat-et-dette-technique.md`
- Décisions d'architecture (ADR), avec un index et un modèle : `docs/decisions/README.md`
