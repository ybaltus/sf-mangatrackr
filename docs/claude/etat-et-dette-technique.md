# État du projet et dette technique

Document **daté** : à relire et à mettre à jour de temps en temps.
Dernière mise à jour : 2026-10-17 (`main` et `dev` identiques, commit `0e0d7b1`).

## État actuel
- 169 tests, 329 assertions, PHPStan niveau 6 sans erreur, `composer audit` sans avis.
- Toutes les dépendances Composer mises à jour dans leurs contraintes (voir l'ADR 0005).
- `declare(strict_types=1)` dans tous les fichiers PHP de `src/` et `tests/` (ADR 0001).
- GrumPHP actif (ADR 0006).
- PHP 8.2 minimum depuis l'ADR 0008 (le lock issu de la mise à jour n'était pas installable
  sous PHP 8.1, ce que la CI a révélé).
- Lockfile npm régénéré avec npm 11 (ADR 0009).
- PR #16 fusionnée dans `main` ; la CI de `main` est verte (PHP 8.2 : Composer, `npm ci`,
  build, cs-fixer, PHPStan, migrations, fixtures, PHPUnit). `dev` est à jour avec `main`.

## À vérifier à la main (peu couvert par les tests)
Ces zones ont reçu de grosses mises à jour. À tester sur l'environnement réel après déploiement :
- l'interface d'administration EasyAdmin (4.10 vers 4.29) ;
- la connexion Google OAuth (Guzzle 7 vers 8) ;
- la modification des chapitres dans la scanthèque (régression corrigée, ADR 0001).

## Dette technique connue
| Sujet | Détail |
|---|---|
| Versions majeures en attente | PHPStan 1.x vers 2.x, PHPUnit 9 vers 11, Doctrine ORM 2 vers 3 et DBAL 3 vers 4 (contraintes `^2` / `^3` dans `composer.json`) |
| Paquets abandonnés | `doctrine/cache`, `symfony/ux-toggle-password` (aucun remplaçant indiqué) |
| Dépendances JavaScript | `npm audit` jamais fait |
| Base de test | `sf_mangatrackr_app_test` et ses droits ont été créés à la main avec le compte root. Un volume recréé les perd : prévoir un script d'init MariaDB (`/docker-entrypoint-initdb.d`) |
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
1. Script d'init MariaDB pour la base de test.
2. `npm audit` et mise à jour des dépendances JS.
3. Tests fonctionnels de l'admin EasyAdmin et de la connexion Google (avec `MockHttpClient`).
4. Montée de PHPStan (2.x, puis niveau 7 ou 8) et de PHPUnit.
5. Migration des attributs `Route`.
