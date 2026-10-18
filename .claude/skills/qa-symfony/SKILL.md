---
name: qa-symfony
description: Lancer et interpréter la QA de MangaTrackr (PHP-CS-Fixer, PHPStan niveau 6, PHPUnit, validation du mapping Doctrine) avant de déclarer une tâche terminée. À utiliser par le testeur, le relecteur et tout agent qui modifie du PHP.
---

# QA MangaTrackr

## Ordre des vérifications
1. `make qa-php-cs-fixer` (le hook Claude le lance déjà après chaque édition PHP ; le relancer
   avant de conclure pour couvrir `src` et `tests`).
2. `make qa-php-stan` — doit sortir sans erreur (niveau 6, `src/` et `tests/`).
3. `make test-only` — PHPUnit avec `APP_ENV=test` (le shell exporte `APP_ENV=dev`, hors
   Makefile utiliser `APP_ENV=test vendor/bin/phpunit`).
4. Si le mapping ou les migrations ont changé :
   `php bin/console doctrine:schema:validate` puis, en test,
   `APP_ENV=test php bin/console doctrine:migrations:status`.
5. `make test` seulement si la base de test doit être réinitialisée (il recrée la base et
   charge les fixtures) ; prévenir le chef avant si la base de test est partagée.

## Lecture des résultats
- Référence connue (2026-10-17) : 169 tests, 329 assertions. Une baisse du nombre de tests
  est une régression à signaler.
- Tests réseau non déterministes : `ApiTenraiCommandTest`, `InitDataCommandTest`,
  `ApiMangaUpdatesCommandTest` appellent les vraies API. Un échec réseau sur ceux-là se signale
  comme tel, sans être masqué ni compté comme une régression du code.
- Base injoignable (`getaddrinfo for database failed`, `Connection refused`) : ce n'est pas un
  échec de code ; statut `bloqué`, à remonter au chef (le conteneur `database` est géré depuis l'hôte).

## Règles
- Ne jamais désactiver une règle PHPStan, ajouter un `@phpstan-ignore` ou un `markTestSkipped`
  pour faire passer la QA sans le dire explicitement dans le rapport.
- Les tests doivent rester rejouables (pas de données résiduelles, ADR 0007).
- Valeurs venant du front, des commandes ou des API : les tester sous forme de chaînes (ADR 0001).
