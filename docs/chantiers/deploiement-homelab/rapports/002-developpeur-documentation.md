# 002 — developpeur — documentation de déploiement et de réinstallation, ADR 0015

- Tâche : T3
- Date : 2026-10-18
- Statut : terminé

## Objectif
Documenter l'exploitation de la production sur la VM `apps-dev` en s'alignant sur les scripts de
T2 : réinstallation complète de la VM vierge à l'application en service, migration unique de
l'installation actuelle, procédures courantes. Ajouter l'ADR 0015, les mentions dans `CLAUDE.md` et
`contexte.md`, et la dette technique.

## Ce qui a été fait
Lus : plan (section T3), README du chantier, rapport 001, `deploy/*.sh`, `deploy/mangatrackr.prod.env.dist`,
`compose.prod.yaml`, `docker/Dockerfile.prod` (+ `.dockerignore`), Makefile (`prod-*`, `pg-*`),
`bin/pg-export.sh` (en-tête), `ressources/HomelabDocumentationApps.md`, `homelab.md`, index et ADR 0014.
`ressources/mangatrackr.prod.env` n'a **pas** été lu. Aucun script, Compose ou Dockerfile modifié.

Fichiers créés ou modifiés :
- `docs/deploiement/apps-dev.md` (nouveau) : fiche de référence (avec les variables de `lib.sh`),
  architecture, règles impératives, tableau des scripts (rôle, codes de sortie), réinstallation
  (5.1 VM, 5.2 Docker depuis le dépôt officiel Debian, 5.3 prérequis PostgreSQL mutualisé, 5.4 clone +
  `install-vm.sh`, 5.5 secrets, 5.6 `init-database.sh` puis `--restore` avec `CONFIRM_TARGET` et
  transfert `scp`, 5.7 `deploy.sh` + `check.sh`), migration de l'installation actuelle (section 6),
  procédures courantes (7.1 redéploiement, 7.2 variables, 7.3 `--migrate`, 7.4 sauvegarde,
  7.5 restauration avec remplacement, 7.6 test de restauration, 7.7 retour arrière), contrôles
  fonctionnels (§8 d'origine), dépannage, sécurité, OAuth / reverse proxy reportés sans paramètre inventé.
- `docs/chantiers/migration-postgresql/homelab.md` : bandeau « Historique » de 3 lignes en tête
  (seule modification, +4 lignes).
- `docs/decisions/0015-deploiement-compose-scripte-apps-dev.md` (nouveau, « Acceptée », 2026-10-18)
  avec alternatives écartées (registre, CI/CD, migrations automatiques) ; ligne ajoutée dans
  `docs/decisions/README.md`.
- `CLAUDE.md` : ligne « Production » dans les commandes, règle `docker/docker.sh` / `docker/Dockerfile`
  dev/démo uniquement, section Chantiers : `deploiement-homelab` en cours, `migration-postgresql`
  noté clos (import fait, d'après le README du chantier).
- `docs/claude/contexte.md` : un paragraphe « Production » dans Environnement.
- `docs/claude/etat-et-dette-technique.md` : section « Dette issue du déploiement sur le homelab »
  (HTTPS + OAuth, `ux-toggle-password` 2.29, pas de healthcheck worker, sauvegardes non externalisées
  et test de restauration à planifier, un seul niveau de retour arrière, scripts non éprouvés en réel).

## Résultats
| Contrôle | Résultat |
|---|---|
| Liens relatifs et ancres (script Python du scratchpad sur les 5 fichiers Markdown touchés, blocs de code exclus) | 0 erreur |
| Secrets dans les fichiers écrits | Aucun (placeholders `<...>` uniquement ; fichier de secrets non lu) |
| `make qa-php-stan` | `[OK] No errors` |
| `make test-only` | `OK (226 tests, 580 assertions)` (référence identique ; 1 dépréciation connue `ux-toggle-password`) |

## Écarts, risques, points non vérifiés
- **Aucune commande de la doc n'a été exécutée** (pas de Docker ni de VM) : en particulier
  l'installation de Docker (format `docker.sources` deb822 du dépôt officiel, repris de mémoire, non
  comparé à la page en ligne du jour), les commandes `psql` manuelles de 7.5 et 7.6, et le contrôle du
  label `com.docker.compose.project` de la section 6.
- **7.5 et 7.6 sont des procédures manuelles sur le PostgreSQL mutualisé** (renommage de la base,
  base jetable `mangatrackr_test_restauration`, `DROP DATABASE`), car les scripts n'ont volontairement
  ni remplacement ni restauration vers une autre base (`init-database.sh` exige que `DB_NAME` égale
  la base de `DATABASE_URL`). À faire relire avec attention (T5).
- Incohérences trouvées dans les fichiers de T2 (**non corrigées**, hors périmètre ; le testeur T4
  travaille dessus) :
  1. `backup.sh` fait sa rotation sur `$BACKUP_DIR/mangatrackr_*.dump`, motif qui couvre aussi les
     dumps de `make pg-export` (`mangatrackr_<base>_<date>.dump`) s'ils sont copiés dans
     `/opt/apps/backups/` : ils seraient comptés et finiraient supprimés. Contourné dans la doc
     (transfert dans `/opt/apps/backups/import/`).
  2. L'en-tête du `.dist` propose `deploy/deploy.sh` après une modification des variables : ce
     script fait aussi `git fetch`/`pull` et une construction, ce n'est pas un simple changement de
     variables. La doc recommande `up -d --no-deps --no-build app|worker`.
  3. `make prod-*` suppose `make` sur la VM (absent d'une Debian minimale) : ajouté aux paquets en 5.1,
     scripts appelables directement.
  4. `install-vm.sh` se trouve dans le dépôt qu'il est censé cloner : sur une VM vierge, il faut
     cloner d'abord (étape alors « déjà en place »). Documenté en 5.4, pas un bug.
- `docs/claude/etat-et-dette-technique.md`, « Pistes pour la suite » point 1, parle encore de
  l'import sur le homelab (fait) : non modifié (hors du périmètre demandé).
- `homelab.md` (historique) mentionne `/ressources/*.dump` comme règle d'ignore, remplacée par
  `/ressources/` en T1 : laissé tel quel, conformément à la consigne.
- Branche à cloner / tirer : la doc dit `main`, et `dev` tant que la PR n'est pas fusionnée.

## Recommandation pour la suite
- T5 (relecteur) : relire en priorité les sections 5.6, 6, 7.5 et 7.6 de `apps-dev.md`.
- T4/T2 : décider du motif de rotation de `backup.sh` (par exemple `mangatrackr_[0-9]*.dump`) et de
  l'en-tête du `.dist` (incohérences 1 et 2) ; mettre la doc à jour en conséquence.
- T6 (utilisateur, sur la VM) : suivre la section 6 telle quelle et signaler tout écart entre les
  `diff` et la liste « écarts attendus ».
- Plus tard : chantier reverse proxy HTTPS + OAuth ; copie des sauvegardes hors VM ; healthcheck du
  worker ; éventuel script de test de restauration pour remplacer les commandes manuelles de 7.6.
