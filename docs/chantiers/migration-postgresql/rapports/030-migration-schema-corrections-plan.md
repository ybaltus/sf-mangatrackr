# 030 — migration-schema — Corrections du plan de bascule, de l'ADR 0013 et de la dette

- Tâche : T7-ter
- Date : 2026-10-18
- Statut : terminé

## Objectif
Traiter les constats I1-I4 et les mineurs M1, M6, M8, M11 du rapport 028, plus les risques oubliés,
dans `plan-bascule.md`, l'ADR 0013, `.gitignore` et `etat-et-dette-technique.md`. Documentation seule.

## Ce qui a été fait
- `plan-bascule.md` :
  - I1 (9.3) : `--yes --replace-existing --dry-run`, explication (code 2 sinon), `CONFIRM_TARGET` exigé ; options vérifiées dans `transfert-mariadb-pgsql.php:12-27,125,171`.
  - I2 (7) : `datlocale` (PG 17+), `daticulocale` en note pour PG 16 et moins ; contrôle de l'extension `unaccent` (`pg_extension` + `SELECT unaccent('é')`).
  - I3 (5) : requête de non-normalisation en `COLLATE utf8mb4_bin` / `CAST … AS BINARY` ; limites des `GROUP BY` en `_ci` ; la simulation `--dry-run` en répétition (code 3) est désignée filet réel ; ajouté en section 15.
  - I4/Q19 : « recalage à la main » supprimé ; D3 `GREATEST(MAX(id), AUTO_INCREMENT-1)` décrite comme appliquée (script et `inventaire.php` par le rapport 029) ; export avec `AUTO_INCREMENT` coché (6.3) et vérifié (6.4, no-go sinon, `grep -c`, requête `information_schema`) ; requêtes de comparaison en 9.3 ; Q19 marquée tranchée ; section 10 corrigée (`MAX+1` retiré).
  - M1 : `user` (nom MariaDB) en 6.2 + contrôle `updated_at` de `manga_statistic` (repli `SUM(nb_view)`).
  - M6 : phrase sur `.gitignore` corrigée. M8 : « dump du 15/10 » remplacé par « dump de référence » (`[date à confirmer]`), 71 029 = 71 014 + 15 versions. M9 : `<app_user>` renommé `<role_app>`. M7 : codes de sortie 0 et 3 reformulés.
  - Risques ajoutés : fuseaux et charset mixte (6.4, R11), sessions/cookies/`APP_SECRET` (12.1, Q16, Q21), NOTIFY Messenger (6.2, 8, R12), emails réels en recette avec `MAILER_DSN` neutre (11, R6), URI OAuth (12.1), crons (6.2), cache (12.1).
- ADR 0013 : Q19 tranchée dans les conséquences, risques listés, statut « Proposée » conservé jusqu'à la répétition, filet réel des doublons.
- `.gitignore` : `/ressources/*.sql.gz` et `/ressources/*.zip` ; `git check-ignore -v` confirme les 3 motifs.
- `docs/claude/etat-et-dette-technique.md` : en-tête « État actuel » 169/329 → 226/580 (seule occurrence périmée). Le tableau de dette compte bien 15 lignes ; le « 16 lignes » ne figure que dans le rapport 027 et le journal, non modifiés (à lire « 15 »).

## Résultats
Aucune commande d'exécution ni écriture en base. Liens : le plan cite le rapport 029, pas encore écrit au moment de ce travail (lien à valider quand il existera).

## Écarts, risques, points non vérifiés
- Les requêtes SQL ajoutées (BINARY, `information_schema`, `pg_extension`) n'ont pas été rejouées.
- Comportement du script après 029 non relu : le plan l'affirme aligné sur D3 d'après la consigne.
- Déclencheur NOTIFY : la baseline n'en crée pas ; création par le transport non vérifiée, marquée `[À VÉRIFIER]`.
- Date du dump de référence du rapport 019 non retrouvée : `[à confirmer]`.
- CLAUDE.md, scripts, code et `etat.md` non touchés. Les tests/PHPStan n'ont pas été relancés (aucun PHP modifié).

## Recommandation pour la suite
Relire le plan après le rapport 029 (cohérence 9.3/10 avec le script réel), puis répondre aux Q1-Q22.
