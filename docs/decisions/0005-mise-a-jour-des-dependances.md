# ADR 0005 : mise à jour des dépendances (92 avis de sécurité)

- Statut : Acceptée
- Date : 2026-10-17
- Commit : `5152982`

## Contexte
`composer audit` signalait 92 avis sur 25 paquets, dont deux critiques (Twig, injection de code
via `{% use %}` ; PhpSpreadsheet, SSRF/RCE dans `IOFactory::load`).

## Décision
`composer update` **dans les contraintes existantes** (`6.4.*`, `^2`, `^3`, ...), sans changer
`composer.json`. Résultat : 158 paquets mis à jour, plus aucun avis. Sauts majeurs venus par
transitivité : Guzzle 7 vers 8, `doctrine/data-fixtures` 1 vers 2, `knp-components` 4 vers 5.

Adaptations nécessaires :
- fixtures : `getDependencies(): array` et `getReference($nom, Classe::class)` (obligatoire en
  data-fixtures 2) ;
- EasyAdmin 4.29 : classes génériques, annotations `@extends AbstractCrudController<Entité>` et
  `@param Événement<Entité>` pour PHPStan ;
- Symfony Console 6.4.48 : l'erreur de validation d'un `ChoiceQuestion` est relancée quand
  l'entrée est épuisée (`ApiMangaUpdatesCommandTest::testModeIsInvalid` attend maintenant
  l'exception).

## Conséquences
- PHP-CS-Fixer 3.95 a reformaté 28 fichiers (commit `3f4b362`, style seul).
- Restent en attente : PHPStan 2, PHPUnit 11, ORM 3 / DBAL 4 (voir la dette technique).
- Les zones EasyAdmin et OAuth Google sont à tester à la main (peu de tests automatiques).
