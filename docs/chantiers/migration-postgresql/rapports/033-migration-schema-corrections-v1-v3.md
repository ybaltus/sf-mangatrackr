# 033 — migration-schema — Corrections V1-V3 et mineurs du rapport 031

- Tâche : T7-quater
- Date : 2026-10-18
- Statut : terminé (documentation seule, aucun test lancé)

## Objectif
Traiter V1, V2, V3 et les mineurs N3 et N5 du rapport 031 dans `plan-bascule.md` et l'ADR 0013.

## Ce qui a été fait
Fichiers modifiés : `docs/chantiers/migration-postgresql/plan-bascule.md`, `docs/decisions/0013-bascule-production-postgresql.md`.
Lecture : 031, 028, 029, 032, `.claude/skills/verif-donnees/SKILL.md`, `config/packages/messenger.yaml`, `.env`.

- V1 : ligne « 6 » ajoutée au tableau de 9.3 (refus avant TRUNCATE, rien modifié, action : réimporter avec l'option cochée) ; codes 0-5
  recontrôlés contre SKILL.md, cohérents. Paragraphe ajouté sur ce que le code 6 ne détecte pas.
- V2 : critère 6.4 réécrit en go/no-go chiffré : nombre d'occurrences `AUTO_INCREMENT=` (16 dans le dump de référence, source : rapport 028,
  « 16 tables, vérifié »), relevé Hostinger avant export, requête `information_schema.tables.auto_increment` sur la source et sur
  `sf_mangatrackr_app_import`, égalité exigée et `>= MAX(id)+1`. ADR 0013 (Q19) et prérequis de 9.3 alignés.
- V3 : toutes les mentions « déclencheur/fonction NOTIFY » retirées (6.2 points 4 et section 8, R12, ADR 0013 ; grep NOTIFY : il ne reste que
  les mentions factuelles `LISTEN/NOTIFY`, `pg_notify`). Faits vérifiés dans le dépôt : `messenger.yaml` (`async` avec `use_notify: true`,
  routes async = `ChatMessage`, `SmsMessage`), `.env` (`doctrine://default?auto_setup=0`). Le détail `symfony/doctrine-messenger` 6.4.48
  (`pg_notify` dans `send()`, `LISTEN`) est repris du rapport 031 et **non revérifié** dans `vendor/` ici. Vérifications (a) table, (b) `messenger:consume`, (c) message de test
  inscrites comme `[À VÉRIFIER en répétition]`.
- N3 : note sur `pg_sequences.last_value` NULL (`is_called` faux) en 9.3 et sur la requête de la section 10.
- N5 : note en section 10 : inventaires 022/023/024 sans clé `auto_increment` à ne pas réutiliser comme attendu, régénérer (032).

## Résultats
Chaque point est traité dans le texte. Aucun secret ni donnée personnelle ajouté ; pas de précision inventée sur le homelab.

## Écarts, risques, points non vérifiés
- Le chiffre 16 est celui de 028 (dump de référence) ; l'hypothèse « les 3 autres = tables vides sans `AUTO_INCREMENT=` » est une déduction, marquée à confirmer.
- Les requêtes SQL ajoutées ne sont pas exécutées. Le grep est un comptage de lignes (`-c`) : si plusieurs occurrences par ligne, utiliser `grep -o | wc -l`.
- Rien de PHP modifié : `make qa-php-stan` et `make test-only` non relancés.

## Recommandation pour la suite
Réviser rapidement le plan puis faire la répétition (rejeu dev avec AI > MAX+1 réel, vérifications Messenger a-c). ADR 0013 reste « Proposée ».
