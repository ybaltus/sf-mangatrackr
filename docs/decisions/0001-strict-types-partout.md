# ADR 0001 : `declare(strict_types=1)` partout et conversion aux frontières

- Statut : Acceptée
- Date : 2026-10-17
- Commits : `bac88b7` (activation), `ef34671` (conversions), `5b7de2d` (correctif)

## Contexte
Les bonnes pratiques du projet (PSR, philosophie Symfony) demandent un typage strict. Aucun
fichier ne déclarait `strict_types`. L'activer a fait apparaître des conversions implicites :
`Uuid` et `UnicodeString` passés à des paramètres `string`, un `int` passé à un `string`, etc.

## Décision
- La règle `declare_strict_types` est active dans PHP-CS-Fixer (avec `setRiskyAllowed(true)`,
  car la règle est « risquée ») et appliquée à `src/` **et** `tests/`.
- Les conversions sont **explicites** et situées **à la frontière** (là où la donnée entre dans
  l'application), pas dans les entités : `Uuid::v4()->toRfc4122()`, `->toString()` sur les
  slugs, `(string)` ou `(float)` sur les valeurs du front.
- Une valeur qui ne se convertit pas est refusée (exception `\InvalidArgumentException`).

## Conséquences
- Régression constatée puis corrigée : le front envoie le nombre de chapitres sous forme de
  chaîne (valeur d'un `<input>`). `setNbChapters(?float)` levait une `TypeError`, qui n'est pas
  un `\Exception` et échappe aux `catch (\Exception)`, d'où une erreur 500. Correctif :
  `ScanthequeService::toNbChapters()`.
- **Piège à retenir** : PHPStan ne voit pas les tableaux `mixed` (JSON du front, arguments de
  commande, réponses d'API). Tout nouveau flux de ce type doit être converti et testé avec des
  valeurs sous forme de chaînes.
