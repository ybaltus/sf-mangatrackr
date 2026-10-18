# 012 — developpeur — recherche insensible casse et accents

- Tâche : T4.8
- Date : 2026-10-18
- Statut : terminé

## Objectif
Recherche insensible à la casse et aux accents (D4) sur PostgreSQL, via une fonction DQL appelant `unaccent()`.

## Ce qui a été fait
- `src/Doctrine/Query/UnaccentFunction.php` (nouveau, `final`) : fonction DQL `UNACCENT(string)` -> `unaccent(...)`.
- `config/packages/doctrine.yaml` : `orm.dql.string_functions.UNACCENT`.
- `src/Repository/MangaRepository.php::searchManga` : `LOWER(UNACCENT(m.title)) LIKE LOWER(UNACCENT(:searchTerm))`.
- `tests/Repository/MangaRepositoryTest.php` : `testSearchMangaIsAccentInsensitive` (terme sans accent -> titre accentué et inversement, aucun résultat pour un terme absent ; données supprimées en `tearDown`, identifiant unique par test).

## Résultats
- `make qa-php-cs-fixer` : 0 fichier corrigé sur 180.
- `make qa-php-stan` : [OK] No errors.
- `make test-only` : OK (193 tests, 546 assertions) ; seulement des dépréciations indirectes existantes.

## Écarts, risques, points non vérifiés
- `searchByTitles` (dédoublonnage MangaUpdates) volontairement laissé sensible aux accents (LOWER seul) : l'élargir risquerait de fusionner des titres distincts ; à valider fonctionnellement.
- Recherche EasyAdmin (vendor, `LOWER(...) LIKE`) non modifiée : reste sensible aux accents.
- `UNACCENT` ne fonctionne que sur PostgreSQL (extension de la baseline) ; pas d'index fonctionnel (volume de la table faible, non mesuré).
- Les jokers `%`/`_` saisis par l'utilisateur ne sont pas échappés (comportement antérieur inchangé).

## Recommandation pour la suite
Décider si le dédoublonnage `searchByTitles` doit aussi ignorer les accents ; envisager un index `unaccent(lower(title))` si la recherche devient lente (nécessite une fonction IMMUTABLE).
