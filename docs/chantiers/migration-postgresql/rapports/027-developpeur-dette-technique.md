# 027 — développeur — Dette technique et contexte après migration

- Tâche : T7 (traçabilité)
- Date : 2026-10-18
- Statut : terminé

## Objectif
Mettre à jour `docs/claude/etat-et-dette-technique.md` et `docs/claude/contexte.md` après la migration PostgreSQL 18 ; lister les lignes de `CLAUDE.md` à ajuster (sans le modifier). Documentation seule.

## Ce qui a été fait
- `docs/claude/etat-et-dette-technique.md` : date 2026-10-18 ; section « Après la migration PostgreSQL » (PG 18 dev/test/CI, 226 tests / 580 assertions, parité 22 tables, 19 séquences, prod Hostinger encore MariaDB, CI jamais exécutée) ; tableau « Dette issue de la migration PostgreSQL » (16 lignes, chacune avec le rapport source) ; ligne « Base de test » réécrite ; première piste pour la suite remplacée.
- `docs/claude/contexte.md` : stack (PG 18), environnement (service `postgres`, `pdo_pgsql`, ICU `fr`, `unaccent`, rôle `CREATEDB`, base de test, `%` à doubler, outils de transfert, MariaDB = source d'import). Document toujours court (~70 lignes).
- Aucun code, etat.md, CLAUDE.md, ADR, plan-bascule.md modifié ; aucune commande sur les bases ; seulement des lectures (docker-compose.yml, .env, doctrine.yaml, rapports).

## Résultats
Tous les points demandés sont tracés, avec leur rapport. Précisions issues des rapports :
- le « 500 sur liste vide » et la validation ASCII (019) sont corrigés (021) et ne sont pas en dette ; le `TypeError` sur `searchTerm` null est corrigé (025) ;
- « tests non écrits du rapport 019 » = test automatisé de l'export CSV et de `searchManga` avec accents + parcours non vérifiés (liste dans la dette).
- Le chiffre 226 tests / 580 assertions vient du journal du chef (contrôle après 024) ; 025 est postérieur, non recompté ici.

## Écarts, risques, points non vérifiés
- `make qa-php-stan` / `make test-only` non relancés : aucun fichier PHP touché (documentation seule).
- Les noms `plan-bascule.md` et ADR 0013 sont cités d'après la mission (rapport 026 en parallèle) : à vérifier une fois écrits.
- « 71 014 lignes » et « 19 séquences » proviennent du rapport 022 ; la valeur « 16 séquences » d'etat.md est périmée (017 : 19).
- Dans etat.md (chef), ligne « Prochaine action » 3 : la dette est maintenant tracée, à marquer faite.

## Recommandation pour la suite
Ajustements proposés de `CLAUDE.md` (non appliqués) :
1. Ligne Stack : « Doctrine ORM 2 / MariaDB » → « Doctrine ORM 2 / PostgreSQL 18 (production encore sur MariaDB jusqu'à la bascule) ».
2. Section Environnement : « service `app` + `database` (MariaDB) » → « service `app` + `postgres` (PostgreSQL 18) ; `database` (MariaDB) conservé seulement comme source d'import jusqu'à la bascule ».
3. Commandes : inchangées ; `make test` fait désormais drop/create PG (rôle `CREATEDB`). Ajouter dans Points d'attention : « `%` à doubler dans un `DATABASE_URL` écrit à la main ; les outils de transfert/parité sont dans `.claude/skills/verif-donnees/` ».
4. Section Chantiers : « En cours : migration-postgresql (MariaDB → PostgreSQL 18) » → préciser « T1-T6 terminés ; T7 bascule homelab en cours (plan-bascule.md, ADR 0013) ; reprise : `/chantier reprendre migration-postgresql` », puis retirer à la clôture.
5. Règles : « Les tests doivent rester rejouables » reste valable ; mentionner `PurgeTestDataTrait` si souhaité.
6. Après la bascule : supprimer toute mention de MariaDB (Stack, Environnement) et mettre à jour la référence « 169 tests » du skill qa-symfony (maintenant 226).
