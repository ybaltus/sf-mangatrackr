# 004 — migration-schema — ADR 0012 migration vers PostgreSQL 18

- Tâche : T4.1
- Date : 2026-10-18
- Statut : terminé

## Objectif
Consigner dans l'ADR 0012 les décisions de l'utilisateur (cible PG 18, D1 à D9, écarts d'index
hérités) avec contexte et conséquences, et l'ajouter à l'index des ADR. Aucune modification de
code, de configuration, de Docker ni de migration.

## Ce qui a été fait
- Lecture : rapport 002 (constats, réponses aux points particuliers, recommandation), rapport
  003 (écarts d'index), `README.md` et `etat.md` du chantier, modèle et ADR 0010/0011.
- Vérification dans `vendor/doctrine/migrations/src/DependencyFactory.php` : le finder par
  défaut est `GlobFinder` (non récursif), `RecursiveRegexFinder` seulement si les migrations
  sont organisées par année/mois. Un sous-dossier `migrations/archive-mariadb/` serait donc
  ignoré aujourd'hui, mais l'ADR impose l'archivage **hors de `migrations/`** pour ne pas
  dépendre de ce détail.
- Créé : `docs/decisions/0012-migration-postgresql-18.md`.
- Modifié : `docs/decisions/README.md` (ligne 0012 dans l'index).
- Journal : une ligne ajoutée. `etat.md` non modifié.

## Résultats
- ADR 0012 (Acceptée, 2026-10-18) : contexte (8 bloquants, `user` réservé, SEQUENCE implicite,
  casse/accents, 2 écarts d'index) ; décision structurée en 4 blocs (cible et coexistence D6,
  schéma et migrations D1/D2/D3/D7/D8 + index, comparaisons et tri D4/D5, transfert D9) ;
  conséquences (MariaDB dev/test ne suit plus le mapping, gel de la prod, correspondance
  `user` → `app_user` dans le transfert et `inventaire.php --compare`, `unaccent` à créer
  partout, création de base ICU explicite, infra T4.2-T4.6, ADR de bascule distinct en T7).
- L'exception à la règle « ne jamais modifier une migration commitée » est explicite, unique,
  justifiée (non rejouables sur PG, dédoublement = modification aussi, base PG remplie par
  import) ; fichiers déplacés, contenu inchangé, règle rétablie après la baseline.

## Passages de CLAUDE.md / contexte.md à mettre à jour à la clôture (non modifiés)
| Fichier:ligne | Passage actuel | À faire |
|---|---|---|
| `CLAUDE.md:6` | `Doctrine ORM 2 / MariaDB` | `Doctrine ORM 2 / PostgreSQL 18` |
| `CLAUDE.md:11` | service `database` (MariaDB) | service PostgreSQL (nom du service selon T4.2) |
| `CLAUDE.md` § Règles | « Ne jamais modifier une migration déjà commitée » | ajouter le renvoi à l'exception unique de l'ADR 0012 |
| `CLAUDE.md:56` | chantier en cours | retirer à la clôture |
| `docs/claude/contexte.md:15` | `DBAL 3 / MariaDB` | `DBAL 3 / PostgreSQL 18` |
| `docs/claude/contexte.md:42` | `database` (MariaDB, hôte `database`) | service et hôte PostgreSQL |
| `docs/claude/contexte.md:46` | `docker/mariadb/init/01-create-test-database.sh` | script `docker/postgres/init/…` (T4.2), collation ICU `fr` |
| `docs/claude/contexte.md:51` | pas de client `mysql` dans `app` | client `psql` (si `postgresql-client-18` ajouté) ou `docker compose exec` |
| `docs/claude/contexte.md` § Architecture | `src/Entity/` | mentionner la table `app_user` |
| `docs/claude/etat-et-dette-technique.md` | — | fin de chantier (règle CLAUDE.md) |

## Écarts, risques, points non vérifiés
- Rien de technique n'a été exécuté (tâche documentaire) ; QA non lancée, aucun PHP modifié.
- Conséquence à surveiller : dès T4.3/T4.4, `make test`/`make test-only` ne tourneront plus sur
  MariaDB (baseline gardée par `abortIf`, mapping `app_user`) ; il faut que PostgreSQL et
  `pdo_pgsql` soient disponibles (rebuild par l'utilisateur) avant de fusionner ces étapes, sinon
  la QA est bloquée.
- Recommandation D2 de l'audit (citation) non retenue par l'utilisateur : le renommage impose
  la correspondance de noms dans `inventaire.php --compare` (à vérifier en T5.4).
- `unaccent` : à créer dans la baseline ou une migration PG ; l'utilisateur applicatif doit
  avoir le droit `CREATE EXTENSION` (extension « trusted » depuis PG 13, droit CREATE sur la
  base suffit) — non vérifié sur un serveur réel.

## Recommandation pour la suite
- T4.2/T4.3 peuvent démarrer sur la base de l'ADR (Docker, env, `doctrine.yaml` avec
  `identity_generation_preferences` et `disable_type_comments`, mapping `app_user`).
- T4.4 : archiver vers un dossier hors `migrations/` (ex. `migrations-archive-mariadb/`), inclure
  `CREATE EXTENSION IF NOT EXISTS unaccent` (ou le laisser à T4.8, à arbitrer par le chef).
- Mettre à jour `CLAUDE.md`/`contexte.md` à la clôture selon le tableau ci-dessus.
