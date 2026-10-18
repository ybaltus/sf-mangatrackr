# 024 — migration-donnees — Garde-fou de cible du transfert (I-A) et codes de sortie

- Tâche : T6 (I-A de 023, mineurs M-D et M3/M-E, M-C)
- Date : 2026-10-18
- Statut : terminé

## Objectif
Empêcher qu'un `DATABASE_URL` de production exporté soit vidé par `rejouer-transfert.sh` : contrôler
l'hôte ET la base, exiger une confirmation explicite de l'appelant, afficher la cible avant TRUNCATE ;
cohérence des codes de sortie ; message explicite pour les FK auto-référentes.

## Ce qui a été fait
Fichiers modifiés (seulement `.claude/skills/verif-donnees/`) :
- `scripts/transfert-mariadb-pgsql.php` : hôte et base lus dans le DSN réellement utilisé, comparés aux
  valeurs attendues (`--expected-host`/`--expected-db`, sinon `TRANSFER_EXPECTED_HOST`/`TRANSFER_EXPECTED_DB`,
  sinon `postgres` / `sf_mangatrackr_app`). Confirmation `CONFIRM_TARGET=<hôte>/<base>` (ou `--confirm-target`)
  égale à la cible réelle, sans valeur par défaut, exigée aussi en `--dry-run`. Refus = code 5 dédié, avant
  toute connexion. `--allow-target` supprimé (remplacé par `--expected-db`). `set_exception_handler` : exception
  non rattrapée = code 3 (au lieu de 255), message sans valeur. Utilisateur de `DATABASE_URL` décodé (M-C).
  FK auto-référente et FK vers table hors périmètre : AVERTISSEMENT explicite sur stderr (M3), et plus de faux
  « dépendances circulaires » pour une FK hors périmètre. Affichage « CIBLE : hôte, base… » + comptages avant TRUNCATE.
- `scripts/rejouer-transfert.sh` : n'ajoute plus rien d'office ; affiche la cible réelle, exige
  `CONFIRM_TARGET` exact (code 5 sinon), puis seulement passe `--yes --replace-existing` ; utilisateur décodé.
- `scripts/inventaire.php` : échec de connexion et exception inattendue = code 3 (conforme à la doc du .sh).
- `SKILL.md` : garde-fous, variables, limites, tables de codes 0-5.

## Résultats
Preuves sur base scratch `sf_mangatrackr_app_scratch` (créée puis supprimée, vérifié : 0 base scratch restante),
source `_import` en lecture seule ; aucune écriture dans `sf_mangatrackr_app` ni la base de test. Mots de passe
uniquement par variables d'environnement.

| Cas | Code | Résultat |
|---|---|---|
| Base ≠ attendue (défaut dev, cible scratch) | 5 | refusé |
| Mauvais hôte (`127.0.0.1` vs `postgres`) | 5 | refusé |
| Hôte attendu configuré autre | 5 | refusé |
| Confirmation absente / fausse (PHP) | 5 | refusé |
| Base `_test` | 5 | refusé |
| `.sh` sans / avec fausse CONFIRM_TARGET | 5 | refusé avant tout appel PHP |
| `.sh` bonne confirmation mais attendus non définis (cible scratch) | 5 | refusé par le PHP |
| Sans `--yes` | 2 | usage |
| Bonne confirmation, scratch sans schéma | 3 | passe le garde-fou, stoppe sur « tables non appariées » (rien touché) |
| Connexion cible impossible | 3 | message générique |
| `inventaire.php` connexion impossible / usage | 3 / 2 | conforme |
| Exception non rattrapée (handler isolé) | 3 (255 sans handler) | prouvé |
| FK auto-référente (table de test) | 3 + AVERTISSEMENT | message affiché |
| Scratch migré (Doctrine) : `--dry-run` | 0 | affichage CIBLE hôte/base/comptages, ROLLBACK |
| Scratch migré : `rejouer-transfert.sh` complet, bonne confirmation | 0 | 71 014 lignes, 22 tables, 19 séquences, parité tolérante et stricte, baseline présente |

`make qa-php-stan` : 0 erreur. `make test-only` : OK (226 tests, 580 assertions).

## Écarts, risques, points non vérifiés
- Le contrôle porte sur l'hôte et la base écrits dans le DSN : un alias DNS ou tunnel nommé `postgres` vers
  la production passerait. Documenté dans SKILL.md. En production, définir explicitement les valeurs attendues.
- Rôle de la confirmation : un opérateur qui exporte `CONFIRM_TARGET` en permanence la neutralise ; elle
  doit être saisie par commande.
- La source a été lue avec l'utilisateur root MariaDB (mot de passe du compose, en variable d'environnement
  seulement, rien d'écrit) ; l'utilisateur applicatif par défaut du script n'a pas été essayé.
- Exception non rattrapée prouvée sur le gestionnaire extrait, pas par un incident réel du script complet.
- `--inject-failure` (M-F) et `--require-baseline` « seule » non traités (hors périmètre).
- Aucune écriture dans dev/test ; `git status` : migrations intactes.

## Recommandation pour la suite
Relecture du garde-fou ; avant T7, fixer `TRANSFER_EXPECTED_HOST/DB` de la production dans la procédure de
bascule et exiger la saisie manuelle de `CONFIRM_TARGET`.
