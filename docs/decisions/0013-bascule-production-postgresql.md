# ADR 0013 : bascule de la production vers PostgreSQL sur le homelab

> **Procédure simplifiée retenue le 2026-10-18 : voir [`homelab.md`](../chantiers/migration-postgresql/homelab.md) ; ce plan complet n'est pas la référence.**

- Statut : Proposée (reste « Proposée » jusqu'à la répétition générale et aux réponses aux questions
  ouvertes ; Q19 est déjà tranchée)
- Date : 2026-10-18
- Contexte lié : ADR 0012, chantier `migration-postgresql` (T7, rapport 026), plan
  [`plan-bascule.md`](../chantiers/migration-postgresql/plan-bascule.md)

## Contexte
L'ADR 0012 a décidé la cible (PostgreSQL 18), le schéma (baseline unique) et l'outil de transfert
(script PHP/DBAL) mais renvoyait la bascule de production à un ADR distinct. Situation :
- la production est sur Hostinger (MariaDB 11.8, schéma gelé) et doit passer sur le homelab de
  l'utilisateur (PostgreSQL 18) ;
- le transfert détruit sa cible ; son garde-fou ne contrôle que l'hôte et la base écrits dans le DSN
  (pas l'identité du serveur) et exige une confirmation `CONFIRM_TARGET` de l'appelant (rapport 024) ;
- la recette sur données réelles (rapport 019) s'est faite en lecture seule : écritures, connexion
  réelle et recalage des séquences par insertion n'ont pas été éprouvés ;
- les spécificités du homelab (hébergement, proxy, TLS, DNS, sauvegardes, déploiement, secrets,
  monitoring) ne sont pas documentées dans le dépôt.

## Décision
- **B1 : bascule en une fenêtre de maintenance** (arrêt des écritures, export final, transfert,
  recette, bascule DNS), pas de double écriture ni de réplication MariaDB → PostgreSQL. Le volume (71 029
  lignes dont 15 versions de migration, 6 comptes) le permet.
- **B2 : répétition générale obligatoire** sur un dump récent, dans une base PostgreSQL distincte,
  jouée à l'identique avant la date de bascule, avec recette en écriture.
- **B3 : gel des écritures avant l'export final** (maintenance applicative incluant les crons et le
  worker, y compris les GET qui écrivent). Sauvegarde Hostinger indépendante avant le gel.
- **B4 : garde-fous du transfert en production** : `TRANSFER_EXPECTED_HOST` et `TRANSFER_EXPECTED_DB`
  fixés explicitement à chaque commande, `CONFIRM_TARGET` saisi à chaque commande et jamais exporté
  durablement ; aucun alias DNS ni tunnel réutilisé pour désigner une cible ; identité du serveur vérifiée à
  la main avant le transfert.
- **B5 : base de production** créée avec ICU `fr` et UTF8, rôle applicatif non super **sans `CREATEDB`**
  (le script d'init de dev le donne pour `make test`) ; schéma uniquement par la baseline Doctrine.
- **B6 : contrôles bloquants** avant ouverture : doublons d'emails à la casse près (index
  `LOWER(email)`), parité tolérante **et** stricte, séquences, recette R1 à R14 du plan. Critères de
  go/no-go explicites (plan, section 12.2).
- **B7 : retour arrière** : MariaDB Hostinger conservé intact, en lecture seule ou en maintenance,
  **30 jours** après la bascule ; pas de réplication inverse. Les écritures faites sur PostgreSQL après
  la bascule sont perdues en cas de retour arrière (ou rejouées à la main), décision de seuil à valider.
  Dump final et sauvegardes MariaDB supprimés après 90 jours.
- **B8 : aucune hypothèse sur le homelab** : les questions ouvertes (plan, section 14) doivent être
  tranchées par l'utilisateur ; l'ADR passera à « Acceptée » avec ces réponses et le compte rendu de la
  répétition.
- L'assistant prépare et relit les commandes mais n'exécute ni export, ni transfert, ni déploiement de
  production, et ne reçoit aucun secret.

## Conséquences
- La bascule ne peut pas être déclarée prête sans répétition réussie, restauration PostgreSQL testée et
  réponses aux questions ouvertes ; la date dépend de ces éléments.
- Le gel du schéma (ADR 0012 D6) dure jusqu'à la fin de la conservation ; un correctif urgent sur
  Hostinger se fait sans migration.
- **Q19 tranchée (2026-10-18, décision de l'utilisateur)** : la décision D3 de l'ADR 0012 s'applique,
  recalage des séquences à `GREATEST(MAX(id), AUTO_INCREMENT - 1)`, afin de ne jamais réutiliser un ID
  supprimé. Le script de transfert et `inventaire.php`, qui recalaient et validaient `MAX(id)+1`, sont
  alignés (tâche migration-donnees, rapport 029). Aucun recalage manuel n'est prévu. Condition : l'export
  phpMyAdmin doit contenir `AUTO_INCREMENT=` (option cochée), vérifié à l'import du dump (go/no-go) : nombre d'occurrences (16 dans le dump de référence) et comparaison de `AUTO_INCREMENT` de `_import` au relevé Hostinger pris avant l'export, car le code de sortie 6 du transfert ne détecte pas un export sans l'option.
- Autres points à trancher avant la répétition : durée de conservation ; perte acceptable d'écritures
  au retour arrière.
- Risques ajoutés après relecture (rapport 028), traités dans le plan : fuseaux horaires (dump en
  `+00:00`), charset mixte `utf8mb3`/`utf8mb4`, sessions fichiers perdues et cookies, Messenger
  (`pg_notify` direct, `auto_setup=0`, table dans la baseline : vérifié en répétition), emails réels pendant la recette (`MAILER_DSN` neutre), URI de redirection OAuth,
  tâches planifiées, cache.
- Le contrôle de doublons d'emails sur `_import` est indicatif (collation `_ci`) ; le filet réel est la
  simulation `--dry-run` du transfert en répétition.
- Points de dette connus à surveiller (rapport 023) : `--inject-failure` présent dans le script,
  `--require-baseline` ne contrôle pas que la baseline est seule, alias DNS non détectés.
- À la clôture : mettre à jour `CLAUDE.md`, `docs/claude/contexte.md` et
  `docs/claude/etat-et-dette-technique.md`.
