# 007 — relecteur — contre-revue des corrections (T5.1)

- Tâche : T5 (contre-revue de T5.1)
- Date : 2026-10-18
- Statut : terminé
- Verdict : **validé — prêt à commiter : oui** (0 bloquant, 0 majeur, 2 mineurs résiduels non bloquants)

## Objectif
Vérifier que les corrections du rapport 006 traitent M1, M2, M3 et les mineurs retenus du rapport
005, sans régression ; juger les mineurs écartés ; relancer `bash -n` et shellcheck ; contrôler
qu'aucun fichier sensible n'est suivi ou à suivre avant commit.

## Ce qui a été fait
- Lecture de `rapports/006-developpeur-corrections-revue.md`.
- Relecture de `deploy/deploy.sh` : en-tête, `check_compose_project`, `compare_built_image`,
  `promote_previous`, `migrations`. Relecture de `deploy/check.sh:43`, du diff de `.dockerignore`,
  de `docs/deploiement/apps-dev.md` (§4, §5.6, §6, §7.7, §9, §10), de l'ADR 0015 et du diff de
  `docs/claude/etat-et-dette-technique.md`.
- `bash -n deploy/*.sh` : OK (6 scripts). `shellcheck -x deploy/*.sh` (binaire du scratchpad) :
  0 remarque, code de sortie 0.
- `git status --porcelain --untracked-files=all`, puis filtrage des chemins sensibles
  (`ressources/`, `.env*` hors `.dist`, `*.dump`, `*.sql`, `*.zip`, caches, `.part`, `settings.local`).
  `git ls-files` passé au même filtre. Recherche d'emails, de hash bcrypt, de `GOCSPX` et de longues
  chaînes hexadécimales dans les fichiers à ajouter.
- `ressources/mangatrackr.prod.env` non lu. Aucune modification hors de ce rapport et du journal.

## Résultats

### Majeurs du rapport 005
| # | Statut | Vérification |
|---|---|---|
| M1 | **corrigé** | `compare_built_image` est appelé après le build et compare l'ID de `$IMAGE` à `SERVICE_ID`. `promote_previous` sort sans retaguer si `SAME_IMAGE=1`. Rien n'est fait en dry-run ni au premier déploiement (`SERVICE_ID` vide). Le chemin normal est inchangé. Rejeu 006 : prev reste v1, `--rollback` revient à v1 |
| M2 | **corrigé** | `--remove-orphans` retiré du `up`. Préflight (valable aussi pour `--rollback`) : refus en code 2 si `postgres` porte le label du projet, ou si `docker ps -a --filter label=...` liste un conteneur autre que app, worker ou `<projet>-*-run-*` ; refus aussi si la liste échoue. Doc §6 : relevé des trois labels, puis arrêt sans contournement |
| M3 | **corrigé** | `.dockerignore` racine : `/ressources/`, `**/*.dump`, `**/*.sql`, `**/*.zip`, `**/*.prod.env` (`.env.local` y était déjà). Aucun fichier `.sql`/`.zip` suivi, donc rien d'utile n'est exclu de l'image de dev. Doc §10 rectifiée ; ligne de dette pour les images de dev déjà construites ; contrôle `docker run ... ls /var/www/ressources` en fin de §6 |

### Mineurs retenus (m1 à m10)
Tous vérifiés :
- m1 : `docker exec -u www-data` dans `check.sh` et dans la doc §9.
- m2 : seul le code 1 avec « Out-of-date! » vaut migration en attente ; tout autre résultat donne `restore_tag` puis un arrêt en code 3, sans sauvegarde ni `migrate`. C'est cohérent avec `UpToDateCommand` 3.9.8.
- m3 : `install-vm.sh` lancé en réel après le dry-run.
- m4 et m9 : en dette.
- m5 : `app:init-datas` et `app:create-user` en interactif, réservés à une base neuve, avec les limites signalées (mise à jour des mangas, mot de passe affiché à la saisie).
- m6 : prérequis « `dev` poussée » et `checkout dev`.
- m7 : contrôle de `/var/www/ressources` sur l'image en service.
- m8 : ADR citant 001 à 006, plus la puce « Nom de projet Compose fixé ».
- m10 : piste de dette à jour, code 2 de `check.sh` documenté.

Aucune régression vue. Les 64 vérifications d'avant sont toujours vertes au rejeu de 006. Les codes de sortie et la doc §4/§9 sont alignés sur le code.

### Mineurs écartés : justifiés
- **Annexe PostgreSQL (m4, seconde partie)** : écarter est raisonnable. Documenter un service qu'on ne connaît pas risque d'induire en erreur. La dette dit explicitement que la réinstallation n'est pas exécutable de bout en bout.
- **Vhost Apache (m9)** : la correction modifie l'image et le dev partage ce vhost. Elle a sa place dans un chantier à part, et la dette est tracée.
- **Ordre T6/T7 (m6, pilotage)** : le push revient au chef, avec l'accord de l'utilisateur. La doc le pose en prérequis.
- **Nuances `pg_restore --list` / `--all-or-nothing`** : aucune correction n'était demandée.

### Mineurs résiduels (non bloquants)
| # | Fichier:ligne | Constat | Suggestion |
|---|---|---|---|
| r1 | `docs/deploiement/apps-dev.md` §6 (paragraphe « S'il existe, l'ancienne image... ») | Après le premier déploiement scripté, l'ancienne image (qui contient peut-être `ressources/`) **devient** `prod-previous`. Elle ne pourra être supprimée qu'après un deuxième déploiement. La formule « une fois `prod-previous` remplacé » est exacte, mais un lecteur pressé pourrait faire `docker image rm -f` juste après le premier et perdre le retour arrière | Préciser « au deuxième déploiement réussi » |
| r2 | `deploy/deploy.sh` (`check_compose_project`, motif `"$COMPOSE_PROJECT_NAME"-*-run-*`) | Le motif accepte tout nom de la forme `<projet>-x-run-y`. Un conteneur étranger nommé ainsi passerait, ce qui reste improbable. Et puisqu'il n'y a plus de `--remove-orphans`, `up` ne le supprimerait pas | Rien à faire maintenant |

### Contrôle avant commit
- 13 fichiers modifiés et 22 nouveaux (scripts, `.dist`, fichiers prod, docs, rapports 001 à 006). Avec ce rapport 007, cela fait 23 nouveaux.
- **Aucun chemin sensible** parmi les fichiers suivis ou à ajouter : pas de `ressources/`, pas de `.env*` hors `deploy/mangatrackr.prod.env.dist`, pas de dump, de `.sql`, de `.zip`, de cache ni de `.part`. `.gitignore` couvre `/ressources/` en entier.
- Aucune valeur sensible dans les fichiers à ajouter : pas d'email réel, pas de hash, pas de jeton. `GOCSPX` n'apparaît que comme motif de recherche dans `check.sh` et dans les rapports.

## Écarts, risques, points non vérifiés
- Toujours aucune exécution réelle de Docker ou PostgreSQL. Trois points restent à confirmer en T6 :
  - l'égalité d'ID d'image quand tout le build sort du cache (M1) ;
  - le format de `docker ps --format '{{.Names}}'` ;
  - le nommage `<projet>-<service>-run-<id>`.
- Le banc de 006 (83 PASS) n'a pas été relancé ici : son résultat est repris du rapport 006.
- `make qa-php-stan` et `make test-only` n'ont pas été relancés : aucun fichier PHP n'a changé depuis 006, où ils étaient verts.

## Recommandation pour la suite
1. T7 (chef, sur accord explicite) : commits découpés comme prévu par le plan, avec ce rapport 007 inclus. Pousser `dev` **avant** T6, puisque la doc §6 l'exige.
2. Corriger r1 en passant (une phrase de doc) ; ce n'est pas un préalable au commit.
3. T6 : suivre la §6 dans l'ordre, et rapporter les labels de app, worker et `postgres` ainsi que le résultat de `ls /var/www/ressources`.
