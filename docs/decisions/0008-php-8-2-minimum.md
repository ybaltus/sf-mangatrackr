# ADR 0008 : PHP 8.2 minimum

- Statut : Acceptée
- Date : 2026-10-17
- Contexte lié : ADR 0005 (mise à jour des dépendances)

## Contexte
`composer.json` déclarait `php >= 8.1` et la CI testait PHP 8.1 et 8.2. Le `composer update` de
l'ADR 0005 a été exécuté sous PHP 8.2.34, sans fixer la plateforme cible : Composer a retenu
des versions qui exigent PHP 8.2 (`knp-paginator-bundle` 6.11, `zipstream-php` 3.1.2,
`webmozart/assert` 2.5, `grumphp` 2.26). Le job PHP 8.1 de la CI a échoué à
`composer install` (« Your lock file does not contain a compatible set of packages »).

## Décision
Abandonner PHP 8.1 plutôt que rétrograder quatre paquets :
- la production (`docker/Dockerfile`) et le devcontainer tournent déjà sous `php:8.2-apache` ;
- PHP 8.1 est en fin de vie (plus de correctifs de sécurité depuis fin 2025).

Changements : `composer.json` (`php >= 8.2`), matrice CI réduite à `['8.2']`, README,
`CLAUDE.md` et `docs/claude/contexte.md`. `composer.lock` : seul le `content-hash` change.
La CI lance aussi PHPStan avec `--memory-limit=1G`, comme le Makefile.

## Conséquences
- Aucun paquet n'est rétrogradé : les 92 avis de sécurité restent corrigés.
- **Leçon** : lancer `composer update` avec `config.platform.php` égal à la version minimale
  supportée, ou vérifier que le lock s'installe sur toute la matrice CI avant de pousser.
