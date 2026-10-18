# 031 — relecteur — Revue de D3 (scripts) et des corrections du plan (029, 030)

- Tâche : T7 (relecture)
- Date : 2026-10-18
- Statut : terminé
- Verdict : **validé avec réserves** (aucun bloquant ; 3 points importants, surtout documentaires)

## Objectif
Vérifier D3 dans le code (`transfert-mariadb-pgsql.php`, `inventaire.php`, `rejouer-transfert.sh`, SKILL.md), la correction de I1-I4 et
des mineurs de 028 dans `plan-bascule.md` / ADR 0013 / dette / `.gitignore`, et la pertinence des risques ajoutés.

## Ce qui a été fait
Lecture seule : `git diff` des scripts et du `.gitignore`, lecture du plan (sections 5-9), de l'ADR 0013, de `messenger.yaml`, `.env`,
du code `vendor/symfony/doctrine-messenger` (v6.4.48), de la baseline. `git check-ignore -v`. Aucune requête sur une base, aucun rejeu,
tests et PHPStan non relancés (aucun PHP de `src/` ni `tests/` modifié).

## Résultats

### Bloquant
Aucun.

### Important
- **V1. Le plan 9.3 ne documente pas le code de sortie 6** (`plan-bascule.md`, tableau des codes, lignes ~345-352 : 0 à 5 seulement). Le script,
  le `.sh` et SKILL.md le définissent. Scénario : opérateur qui voit un code 6 le jour J sans action prévue. Ajouter la ligne « 6 | AUTO_INCREMENT
  source NULL/absent, rien modifié | réimporter le dump avec l'option cochée (6.3) ».
- **V2. Le garde-fou « AUTO_INCREMENT » ne détecte presque rien, et le contrôle 6.4 est faible.** Le code 6 ne se déclenche que sur NULL ou `< 1`.
  Un dump exporté sans l'option donne, après import InnoDB, AI = MAX+1 (ou 1 si vide), jamais NULL pour une table à clé auto-incrémentée :
  le script retombe en silence sur `MAX+1`, et `inventaire.php` (qui lit la même valeur dans `_import`) valide aussi : D3 est alors violée sans
  signal (le rapport 029 l'admet, p. « Écarts »). Le plan s'en remet donc entièrement à 6.4, dont le critère `grep -c "AUTO_INCREMENT=" > 0`
  passe dès qu'une seule table en porte une. Correction : comparer au nombre attendu (16 occurrences dans le dump de référence, pour 19 tables à
  séquence ; les 3 autres sont vides ou à AI=1) ou, mieux, comparer avec les AUTO_INCREMENT relevés sur le serveur Hostinger avant l'export
  (`information_schema.tables` côté source) ; rendre le critère « go/no-go » explicite sur ce nombre.
- **V3. Trigger NOTIFY : l'hypothèse du plan est fausse, le `[À VÉRIFIER]` peut être tranché.** Le plan (6.2 point 4, section 8, R12, ADR 0013)
  suppose un déclencheur NOTIFY créé « à l'initialisation du transport ». Dans `symfony/doctrine-messenger` v6.4.48 (composer.lock) : aucun
  trigger ni fonction n'existe ; `Connection::send()` exécute `SELECT pg_notify(table_name, queue_name)` dans la transaction d'insertion
  (`Connection.php:468`) et `PostgreSqlConnection::get()` fait `LISTEN "messenger_messages"`. `getExtraSetupSqlForTable()` renvoie `[]`. De plus
  `.env` fixe `MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0` (pas de création auto de table ; la baseline crée déjà
  `messenger_messages` + index, `Version20261018145659.php:86-87`). À vérifier réellement en répétition : (a) la table et l'index existent après
  baseline ; (b) `messenger:consume async -vv` démarre sans erreur `LISTEN` ; (c) un message de test (`ChatMessage`/`SmsMessage` sont les seules
  routes async, `messenger.yaml`) est consommé. Retirer les mentions « déclencheur/fonction NOTIFY » de R12, 6.2, 8 et de l'ADR 0013 ; le risque
  résiduel est faible (file quasi inutilisée : `SendEmailMessage` est routé en `sync`).

### Mineur
- **N1. Bornes D3 : correctes.** Transfert : `last = max(MAX, AI-1)` ; `last < 1` → `setval(1,false)` (prochaine = 1), sinon `setval(last,true)`
  (prochaine = last+1 >= AI, jamais décalée). `inventaire.php` : attendu = `max(MAX+1, AI)` ; PG `next = called ? last+1 : last` : cohérent
  des deux côtés, y compris table vide à AI>1 (prochaine = AI) et AI=1 (prochaine = 1). Lecture de l'AI avant tout TRUNCATE (code 6, rien
  modifié), calcul dans la transaction, `setval` après COMMIT (code 4 conservé), `--dry-run` n'applique rien. Le renommage `user`→`app_user` est
  géré des deux côtés (`$sourceNames`, `--rename` appliqué avant l'écart des séquences, et repli `explode`). Seule limite : `AI` d'un inventaire
  PG ancien sans clé `table` est géré par repli ; un inventaire MariaDB ancien est signalé en écart (voulu).
- **N2. Codes 0-6 cohérents** entre PHP (`EXIT_AUTO_INCREMENT_MISSING = 6`, `fail(..., 6)`), `.sh` (propagé par `set -e`, en-tête documenté) et SKILL.md.
  Dans le `.sh`, `RC`/`RC2` > 1 sont propagés tels quels.
- **N3. Requête 9.3 `pg_sequences.last_value`** : NULL si la séquence n'a jamais été appelée (`is_called=false`, table vide à AI=1) ; préciser
  « NULL = prochaine valeur 1 », sinon fausse alerte. Idem la formule « last_value = GREATEST(MAX, AI-1) » ne vaut que pour `is_called=true`.
- **N4. `[À VÉRIFIER … valeurs de manga_user_track et user_track_list]`** (plan ~372) reste ouvert : normal, c'est de la répétition (R-dev, rejeu à faire).
- **N5. Régénération des inventaires** : nécessaire (l'inventaire MariaDB doit contenir `auto_increment`) ; elle est faite par `rejouer-transfert.sh`
  à chaque exécution. Les JSON 022/023/024 déjà sur disque, sans cette clé, donneront 19 écarts si comparés avec le nouveau code : le noter si on les
  réutilise comme « attendu ».
- **N6. SKILL.md** : la phrase sur `--dry-run` est devenue une ligne longue mal coupée après l'insertion (lisibilité seulement).
- **N7. `etat.md:25`** parle encore de « recalage des 16 séquences » (19 d'après 022/029) ; ne pas modifier ici, à signaler au chef. De même le
  journal et 027 disent « 16 lignes » de dette (15), relevé en 028/M11 et non corrigé dans ces fichiers, comme l'a noté 030.

### Statut des constats de 028
| Constat | État | Preuve |
|---|---|---|
| I1 dry-run | corrigé | plan 9.3 : `--yes --replace-existing --dry-run`, `CONFIRM_TARGET`, explication du code 2 ; conforme à `transfert-mariadb-pgsql.php` |
| I2 ICU | corrigé | `datlocprovider, datlocale` (PG 17+), note `daticulocale`, `pg_extension` + `unaccent('é')` |
| I3 emails | corrigé | `COLLATE utf8mb4_bin` / `CAST AS BINARY`, limites des `GROUP BY`, `--dry-run` code 3 désigné filet |
| I4 / Q19 | corrigé côté code et texte (V1, V2 résiduels) | « recalage manuel » supprimé ; D3 décrite ; 6.3/6.4 ; plus d'occurrence de `MAX+1` hors ADR 0013 qui la cite comme ancien état |
| M1 `user` | corrigé | 6.2 point 3 |
| M2 fuseaux, M3 charset | corrigés | 6.4 (a)(b), R11 |
| M4 sessions | corrigé | 12.1, Q16, Q21 |
| M5 Messenger | traité mais sur une prémisse inexacte (V3) | |
| M6 `.gitignore` | corrigé | `git check-ignore -v` : motifs `/ressources/*.sql`, `*.sql.gz`, `*.zip` ; un `.json` reste non ignoré, dit dans le plan (l. 78) |
| M7, M8, M9 | corrigés | codes 0/3 reformulés ; « dump de référence » ; `<role_app>` |
| M10 emails recette | corrigé | section 11, R6, `MAILER_DSN` neutre |
| M11 | partiel | « État actuel » 226/580 corrigé ; « 16 lignes » reste dans 027/journal (N7) |
| M12 | OK | ADR 0013 cohérent avec 0012, statut « Proposée » |

### Contrôles transverses
- Liens/chemins : rapports 017-030, ADR 0012/0013, `Version20261018145659.php`, scripts : existent. Le plan cite le rapport 029 : il existe désormais.
- Aucun secret, hash ni donnée personnelle dans les fichiers relus ; placeholders (`<mot_de_passe>`, `[À COMPLÉTER]`) ; rien d'inventé sur le homelab.
- SQL plausible : `datlocale`/`datlocprovider` (PG 17+) exact ; `CREATE DATABASE … LOCALE_PROVIDER icu ICU_LOCALE 'fr'` valide ; `unaccent` OK ;
  les requêtes binaires MariaDB sont plausibles mais **non exécutées**.

## Écarts, risques, points non vérifiés
Non exécuté : les requêtes SQL du plan, le rejeu D3 en dev, `make test-only` / `make qa-php-stan` (confiance dans 029 : 226 tests, 580 assertions).
Les scénarios AI>MAX+1 / NULL ont été simulés par l'agent 029 sur copies de scripts, pas sur une vraie table MariaDB à AI élevé.

## Recommandation pour la suite
T7 peut être close une fois Q1-Q22 traitées par l'utilisateur et un rejeu dev effectué, **à condition** de corriger d'abord V1 (ligne code 6),
V2 (critère go/no-go AUTO_INCREMENT chiffré) et V3 (retrait de l'hypothèse « trigger NOTIFY » ; vérification (a)-(c)), corrections documentaires de quelques
lignes sans nouveau rejeu. Au rejeu dev, vérifier en plus que les 19 séquences sont à écart 0 et que la prochaine valeur de `manga_user_track` et
`user_track_list` est >= AUTO_INCREMENT de `_import`. L'ADR 0013 reste « Proposée » jusqu'à la répétition.
