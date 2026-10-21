# ADR 0017 : sauvegardes de la base copiées vers le NAS (rsync, clé en écriture seule)

- Statut : Acceptée
- Date : 2026-10-21
- Contexte lié : ADR 0015 (`deploy/backup.sh`), dépôt `proxy-setup` (`BACKUP-NAS.md`)

## Contexte
`deploy/backup.sh` écrit les dumps PostgreSQL dans `/opt/apps/backups` sur la VM `apps-dev` : si la VM
disparaît, la base et ses sauvegardes partent ensemble. La sauvegarde n'était ni planifiée ni copiée.

## Décision
- **Copie vers le NAS** (OpenMediaVault) par `deploy/backup-offsite.sh` : rsync sur SSH, clé dédiée
  (`/opt/apps/secrets/backup-nas.key`, 600), `known_hosts` dédié, `StrictHostKeyChecking=yes`. Envoie le
  dernier `backup_<base>_*.dump` seulement ; refuse de tourner sans `OFFSITE_REMOTE`.
- **Côté NAS, clé restreinte** : `rrsync -wo <dossier>` depuis l'IP de la VM : écriture seule dans un
  dossier, donc une VM compromise ne peut ni lire, ni lister, ni supprimer les sauvegardes déposées.
  Rotation (90 jours) par cron sur le NAS. Procédure : `BACKUP-NAS.md` du dépôt `proxy-setup`.
- **Planification** : `deploy/systemd/mangatrackr-backup.{service,timer}` (quotidien 03:15, `backup.sh`
  puis `backup-offsite.sh`), installés à la main (docs/deploiement/apps-dev.md §7.4).
- Même mécanisme pour la VM proxy (archive Caddy, `vm/backup-proxy.sh`).

## Conséquences
- Aucune alerte en cas d'échec (journal systemd seulement) : à consulter après une panne du NAS.
- Les scripts n'ont pas été exécutés contre le vrai NAS à la rédaction (essais avec un `rsync` simulé) ;
  le premier envoi réel et un test de restauration (`pg_restore --list` sur un dump reçu) restent à faire.
- La base contient des données personnelles : le NAS et le canal SSH doivent rester sous contrôle.
