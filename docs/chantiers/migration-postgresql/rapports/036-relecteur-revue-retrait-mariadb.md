# 036 — relecteur — Revue du retrait de MariaDB (T8)

- Tâche : T8
- Date : 2026-10-18
- Statut : terminé

## Objectif
Relire en lecture seule le diff non commité de T8 (rapport 035 et corrections du chef) :
cohérence de l'infra, absence de références orphelines, exactitude des commentaires et des
docs vivantes, respect des décisions de l'utilisateur (historique intact, outils conservés).

## Ce qui a été fait
- `git status`, `git diff`, `git diff --cached` (3 suppressions indexées : `compose.yaml.save`,
  `compose.override.yaml.save`, `docker/mariadb/init/01-create-test-database.sh`).
- `git grep -n -i -E 'mariadb|mysql|db-data'` puis recherche ciblée de `MARIADB_VERSION`,
  `DATABASE_URL_MARIADB`, `mangatrackr_db`, `docker/mariadb`, `*.yaml.save`, `host=database`,
  `@database`, `3306`, `pdo_mysql`, `DB_ROOT_PASSWORD`, `Hostinger` (Makefile, `bin/`, CI,
  `.devcontainer/`, `docker/`, `config/`, `.claude/`).
- Lecture complète de `docker-compose.yml`, `.devcontainer/docker-compose.devcontainer.yml`,
  `.devcontainer/devcontainer.json` (début), `docker/docker.sh`, `etat.md`, fin du journal,
  début de `homelab.md`, en-tête d'`etat-et-dette-technique.md`.
- `make qa-php-stan` (hôte) : `[OK] No errors` (183 fichiers). `sh -n` sur le script d'init
  PostgreSQL : OK. `lint:yaml` impossible sur l'hôte (PHP sans `dom`) ; la validité de Compose
  repose sur `docker compose config -q` du rapport 035 (aucune commande docker lancée ici).
- Aucun fichier modifié hors de ce rapport et d'une ligne de journal.

## Résultats
| Point | Verdict |
|---|---|
| `docker-compose.yml` : service `database`, `depends_on`, volume `db-data` | retirés, aucun reste ; `app` dépend de `postgres` seul ; volumes `pg-data` seul |
| Override devcontainer | `DATABASE_URL_MARIADB` et bloc `database:` retirés ; `postgres:` (5432) et `claude-config` cohérents |
| `MARIADB_VERSION`, `DATABASE_URL_MARIADB` | plus lus nulle part (Makefile, `bin/`, CI, compose, `config/`) ; seules mentions dans l'historique |
| `pdo_mysql` | retiré des deux Dockerfiles ; aucun code de `src/`, `config/`, `bin/`, Makefile ni CI n'en dépend ; seuls les scripts archivés de `.claude/skills/` l'utilisent (DSN `mysql:host=database` par défaut, paramétrable) |
| Suppressions indexées | les `.save` ne sont chargés ni par Compose ni par `devcontainer.json` ; `docker/mariadb/` n'est plus monté |
| Commentaires de code (5 fichiers PHP/YAML, 1 script) | exacts pour PostgreSQL (NULL en tête en `DESC`, comparaison sensible à la casse) ; diff limité aux commentaires, aucune logique modifiée |
| Docs vivantes (README, CLAUDE.md, contexte.md, dette) | cohérentes entre elles (PostgreSQL seule base, homelab cible, outils archivés) |
| Bandeaux d'archive | présents : 2 SKILL.md, agents `migration-importeur` et `migration-auditeur`, CLAUDE.md l. 45, contexte.md l. 54 |
| Historique | ADR, rapports, `plan-bascule.md`, `migrations-archive-mariadb/`, `migrations/` non modifiés ; journal : ajouts seuls (3 lignes) |
| « Hostinger abandonné » | fondé (journal l. 107, recadrage de l'utilisateur en T7) |
| PHPStan | OK |

Occurrences restantes de `mariadb|mysql|db-data` : toutes justifiées (historique, outils
archivés et leurs bandeaux, `.claude/settings.json` permission d'`import-dump.php`,
`skills/chantier` l. 59, `composer.lock`, mentions du retrait dans les docs vivantes, documents
du chantier). Le tableau du rapport 035 est exact.

### Constats
Aucun bloquant, aucun majeur.

1. **mineur** — `.claude/agents/migration-donnees.md` (en tête, après le front matter) : pas de
   bandeau d'archive, alors que l'agent repose entièrement sur `transfert-mariadb-pgsql.php`
   (source MariaDB `database:3306`) et que `etat-et-dette-technique.md:31` annonce « agents
   `migration-*` » archivés. Scénario : une session future lance `migration-donnees` sans savoir
   que la source n'existe plus dans l'infra ; échec de connexion PDO. Correction : ajouter le même
   bandeau (le plan ne listait que deux agents, d'où l'oubli).
2. **mineur** — `docs/decisions/README.md:21` : ADR 0013 toujours « Proposée » (bascule depuis
   Hostinger/MariaDB, plan-bascule), alors que les docs vivantes déclarent Hostinger abandonné et
   que `homelab.md` remplace le plan. La décision « Hostinger abandonné, import direct homelab,
   MariaDB retiré » est structurante (règle CLAUDE.md : un ADR). Scénario : un lecteur de l'index
   des ADR croit la bascule MariaDB encore prévue. Correction (à décider par le chef/l'utilisateur,
   l'historique étant figé) : nouvel ADR 0014 qui remplace 0013, et statut de 0013 dans l'index
   passé à « Remplacée par 0014 » (l'index est un document vivant ; le fichier de l'ADR reste intact).
3. **mineur** — `docs/claude/etat-et-dette-technique.md:64` : « `DB_ROOT_PASSWORD` … à supprimer
   à la bascule » : la bascule MariaDB n'existe plus ; le secret n'est lu par aucun workflow.
   Correction : « à supprimer (inutile depuis T4) ».
4. **mineur** — `docs/chantiers/migration-postgresql/README.md:26` : « pas de `pdo_pgsql` dans le
   conteneur `app` » est faux depuis T4 (et « pas de client `mysql` » n'a plus d'objet). Document
   de cadrage, pré-existant à T8 ; correction facultative : préciser « (au démarrage du chantier) ».
5. **mineur** — `docs/claude/contexte.md:15` : « 8.2 … en production » : la production cible est
   désormais le homelab, dont la version de PHP n'est pas documentée dans le dépôt. Pré-existant ;
   à vérifier avec l'utilisateur.

## Écarts, risques, points non vérifiés
- Nouvelle image sans `pdo_mysql` non construite ni testée (pas de docker ici, ni en 035) :
  la validation dépend du rebuild par l'utilisateur et du testeur (037).
- `make test-only` non relancé par moi (PHP de l'hôte sans `pdo_pgsql`) ; je m'appuie sur le
  rapport 035 (226 tests, 580 assertions, ancienne image).
- `.env.local` non lu (interdit) : s'il contenait encore un `DATABASE_URL` en `mysql://`, la
  variable d'environnement du devcontainer l'emporte en dev et en test, mais pas sur un autre
  poste. Risque faible, à signaler à l'utilisateur.
- Retour arrière : `git restore --staged --worktree <fichier>` pour les 3 suppressions et
  `git checkout -- <fichier>` pour le reste (rien de commité).

## Recommandation pour la suite
Verdict : **validé avec réserves** (5 mineurs, aucun bloquant). Corriger au moins le constat 1
(bandeau `migration-donnees.md`) et le 3 avant la validation par le testeur ; faire trancher le 2
(ADR 0014) par l'utilisateur. Puis testeur (037) après rebuild du devcontainer.
