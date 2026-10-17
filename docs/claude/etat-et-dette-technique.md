# État du projet et dette technique

Document **daté** : à relire et à mettre à jour de temps en temps.
Dernière mise à jour : 2026-10-17 (branche `dev`, en avance sur `main` : prochaine PR à ouvrir).

## État actuel
- 169 tests, 329 assertions, PHPStan niveau 6 sans erreur, `composer audit` sans avis.
- Toutes les dépendances Composer mises à jour dans leurs contraintes (voir l'ADR 0005).
- `declare(strict_types=1)` dans tous les fichiers PHP de `src/` et `tests/` (ADR 0001).
- GrumPHP actif (ADR 0006).
- PHP 8.2 minimum depuis l'ADR 0008 (le lock issu de la mise à jour n'était pas installable
  sous PHP 8.1, ce que la CI a révélé).
- Lockfile npm régénéré avec npm 11 (ADR 0009).
- `npm audit fix` sans version majeure : 58 vulnérabilités ramenées à 27, plus aucune critique ; `webpack` borné à `~5.111.1` (ADR 0010).
- PR #16 fusionnée dans `main` ; la CI de `main` est verte (PHP 8.2 : Composer, `npm ci`,
  build, cs-fixer, PHPStan, migrations, fixtures, PHPUnit). `dev` est à jour avec `main`.

## Vérifications manuelles
Faites à la main le 2026-10-17 (par le mainteneur), avec les versions mises à jour :
- interface d'administration EasyAdmin (4.29) : OK ;
- connexion Google OAuth (Guzzle 8) : OK ;
- scanthèque, dont la modification des chapitres (ADR 0001) : OK.

La couverture **automatique** de ces zones reste faible (voir la dette technique).

## Dette technique connue
| Sujet | Détail |
|---|---|
| Versions majeures en attente | PHPStan 1.x vers 2.x, PHPUnit 9 vers 11, Doctrine ORM 2 vers 3 et DBAL 3 vers 4 (contraintes `^2` / `^3` dans `composer.json`) |
| Paquets abandonnés | `doctrine/cache`, `symfony/ux-toggle-password` (aucun remplaçant indiqué) |
| Dépendances JavaScript | 27 vulnérabilités npm restantes (11 hautes, 16 modérées), toutes dans l'outillage de build (surtout le serveur de développement Webpack, non utilisé). Elles exigent `@symfony/webpack-encore` 5, `tailwindcss` 4 et `webpack-notifier`. Borne `webpack ~5.111.1` à retirer avec Encore 5 (ADR 0010). Autres majeures en retard : `flowbite` 4, `htmx.org` 2, `postcss-loader` 8, `webpack-cli` 7 |
| Base de test | Script d'init MariaDB ajouté (`docker/mariadb/init/`), **non testé de bout en bout** (pas de Docker dans le conteneur `app`) : à valider en recréant le volume `db-data`. Sur un volume existant, il faut le lancer à la main (voir `contexte.md`) |
| Lockfile npm | Les liens `file:vendor/symfony/ux-*/assets` font planter npm 10.8 (Node 20) quand les assets UX changent ; lockfile régénéré avec npm 11 (ADR 0009). Envisager Node 22/24 ou npm 11 dans le devcontainer et la CI |
| Environnement | `APP_ENV=dev` exporté par le shell du devcontainer, contourné dans le Makefile |
| Tests réseau | Plusieurs tests de commandes (`ApiTenraiCommandTest`, `InitDataCommandTest`, `ApiMangaUpdatesCommandTest`) appellent les vraies API : réseau requis, résultats non déterministes |
| Admin | Presque pas de tests sur EasyAdmin (un test de redirection du dashboard) |
| `strict_types` | Les tableaux `mixed` venant du front, de la ligne de commande ou des API échappent à PHPStan : un `int` ou une chaîne mal typé peut lever une `TypeError` à l'exécution. Audit non exhaustif |
| Routes | `Symfony\Component\Routing\Annotation\Route` (déprécié en 6.4) à remplacer par `Symfony\Component\Routing\Attribute\Route` |
| Fichiers parasites | `compose.yaml.save`, `compose.override.yaml.save`, `tests/Command/ApiTenraiTestCommandTest.php.save` |
| Symfony 6.4 | Version LTS ; la suivante (7.4 LTS) demandera une migration |

## Pistes pour la suite
Ordre suggéré :
1. Valider le script d'init MariaDB en recréant le volume `db-data` (sur l'hôte : `docker compose down -v`, **qui efface aussi la base de développement** ; prévoir `make first-install` ensuite).
2. Montées majeures JS, une par une : d'abord `@symfony/webpack-encore` 5 (retirer la borne webpack), puis `tailwindcss` 4 (chantier à part), `htmx.org` 2, `flowbite`.
3. Tests fonctionnels de l'admin EasyAdmin et de la connexion Google (avec `MockHttpClient`).
4. Montée de PHPStan (2.x, puis niveau 7 ou 8) et de PHPUnit.
5. Migration des attributs `Route`.
