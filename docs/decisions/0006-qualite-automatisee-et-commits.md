# ADR 0006 : qualité automatisée, GrumPHP actif, règles de commit

- Statut : Acceptée
- Date : 2026-10-17
- Commits : `c9fb699` (Makefile), `5b7de2d` et suivants (GrumPHP actif)

## Contexte
`grumphp.yml` était entièrement commenté en local : aucune vérification avant commit. Le
Makefile ne vérifiait que `src/`, PHPStan manquait de mémoire (128M par défaut) et le shell
exporte `APP_ENV=dev`, ce qui faisait échouer PHPUnit.

## Décision
- GrumPHP est actif (config versionnée) : `make grumphp-checker` (PHP-CS-Fixer, PHPStan,
  `symfony security:check`) et contrôle du message de commit.
- Message de commit : **objet <= 60 caractères, lignes du corps <= 72**, objet sur une ligne,
  sans point final.
- Makefile : `PHPSTAN` avec `--memory-limit=1G`, `PHPUNIT` préfixé de `APP_ENV=test`,
  `PHPCSFIXER` avec `--config=.php-cs-fixer.dist.php` et cibles `src tests`.
- Avant de déclarer une tâche terminée : `make qa-php-stan` et `make test-only`.

## Conséquences
- Un commit prend environ une minute (les contrôles tournent sur l'arbre entier, pas sur
  l'index). Pour un commit découpé en plusieurs, l'arbre de travail doit être cohérent à chaque
  étape.
- Les commits antérieurs à `5b7de2d` peuvent dépasser 60 caractères (GrumPHP était désactivé).
