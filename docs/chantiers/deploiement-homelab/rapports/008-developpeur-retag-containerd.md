# 008 — developpeur — retag de prod-previous compatible containerd

- Tâche : T6.1
- Date : 2026-10-19
- Statut : partiel (script corrigé et rejoué sur faux Docker ; `make test-only` bloqué par la base injoignable ; rien vérifié sur un vrai containerd)

## Objectif
Sur la VM (Docker 29.8.1, magasin containerd), `deploy.sh` faisait `docker tag <ID> mangatrackr:prod-previous`
après `compose build`. L'ancienne image avait déjà perdu son enregistrement (son seul tag `prod` était
déplacé) : « No such image: sha256:34a3d90... ». Il faut tout faire par nom, avant que le tag bouge.

## Ce qui a été fait
Fichiers modifiés : `deploy/deploy.sh`, `docs/deploiement/apps-dev.md` (§4, §6, §7.7, dépannage),
`docs/decisions/0015-deploiement-compose-scripte-apps-dev.md` (puce « retour arrière par image »).
`deploy/lib.sh` inchangé.

Conception (écart assumé par rapport à la consigne « poser prod-previous avant le build ») :
poser directement `prod-previous` avant le build écraserait le vrai retour arrière si le build
redonne la même image (garantie T5.1). On épingle donc l'image en service sous un tag de travail
`mangatrackr:prod-service` AVANT le build, par nom, puis `prod-previous` est posé depuis ce tag
juste avant `up` (comme avant). Conséquence : une tentative interrompue (build KO, migration en
attente) ne touche jamais `prod-previous`.

`record_service_image` (avant le build) :
| Situation | Comportement |
|---|---|
| aucun conteneur et pas de tag `prod` | message « premier déploiement », aucun tag |
| conteneur sans tag `prod` | image du conteneur retrouvée par ID, épinglée |
| `prod` désigne l'image du conteneur | `docker tag mangatrackr:prod mangatrackr:prod-service` |
| `prod` désigne une autre image, celle du conteneur reste retrouvable | avertissement, épinglage par l'ID retrouvé (valable car résolvable) |
| image du conteneur introuvable (cas de la VM) | avertissement « aucun retour arrière possible pour ce cycle », `prod-previous` non modifié, déploiement poursuivi (rc 0) |

- `compare_built_image` compare maintenant `prod` (après build) à `prod-service`, deux ID lus par la
  même commande `docker image inspect` (pas de mélange entre l'ID d'un conteneur et celui d'une
  image, dont le format peut différer avec containerd).
- `promote_previous` : `docker tag mangatrackr:prod-service mangatrackr:prod-previous` (par nom) ;
  inchangé si image identique ou rien d'épinglé.
- `restore_tag` : `docker tag mangatrackr:prod-service mangatrackr:prod` (par nom, plus d'ID).
- `--rollback` n'a pas changé (il était déjà par nom : `prod-previous` → `prod`), vérifié.
- Inchangés : pas de `--remove-orphans`, jamais de `down`, `--dry-run` affiche les tags sans les poser.
- Le tag `prod-service` n'est pas supprimé après coup (éviter un `rmi` risqué) ; il épingle l'image du
  déploiement précédent jusqu'au suivant (documenté).

## Résultats
| Contrôle | Résultat |
|---|---|
| `bash -n deploy/*.sh` | OK |
| shellcheck 0.10.0 `-x` (depuis `/var/www`) | 0 remarque |
| Banc simulé (`scratchpad/t4/replay-t41.sh`, faux docker passé en mode containerd : un ID n'est résoluble que s'il porte un tag, `docker tag <ID inconnu>` échoue) | **102 PASS, 0 FAIL** (83 d'avant + 19 nouveaux, section « T6.1 ») |
| Même banc contre l'ANCIEN script (HEAD) | 47 FAIL : le faux containerd reproduit bien l'échec de la VM |
| `make qa-php-stan` | `[OK] No errors` |
| `make test-only` | **échec : 226 tests, 70 erreurs, 31 échecs**, tous `SQLSTATE[08006] could not translate host name "postgres"` (base injoignable depuis ce conteneur de travail). Aucun PHP modifié ; statut « bloqué » pour ce contrôle, non rejoué en vert |

Nouveaux cas : nominal (épinglage avant build, prev posé après build et avant up, aucun tag par ID),
rollback (réel et dry-run), image en service introuvable (rc 0, message, prev non modifié ou conservé
avec build KO, dry-run), `prod` déplacé mais image retrouvable, build KO, migration en attente
(`prod` remis par nom), premier déploiement, dry-run nominal, image identique (prev conservé).

Banc : `/tmp/claude-0/-var-www/f578a951-56ed-48f3-8d75-06a131c21ff4/scratchpad/t4/` (faux docker modifié ;
sauvegarde de l'ancien : `fakebin/docker.t51.bak`). Rejeu : `bash .../t4/replay-t41.sh`.

## Écarts, risques, points non vérifiés
- Rien n'a été exécuté contre un vrai Docker/containerd. Suppositions : `docker tag <nom> <nom>` sur
  une image présente fonctionne sous containerd (cas standard) ; `docker image inspect <ID du
  conteneur>` échoue proprement quand l'image est perdue (c'est ce que la VM a montré pour `docker tag`) ;
  le tag de travail `prod-service` suffit à garder l'ancienne image vivante pendant le build.
- Sur la VM, le prochain `deploy.sh` annoncera « aucun retour arrière possible pour ce cycle » et
  déploiera quand même ; `prod-previous` n'existera qu'après le déploiement suivant.
  Pendant ce cycle, en cas d'échec après `up`, il n'y aura pas de `--rollback` possible : sauvegarde déjà
  faite, restaurer et redéployer un commit précédent (`--ref`).
- `make test-only` non vert ici pour cause d'environnement (base `postgres` non résolue).
- Après un `--rollback`, `prod-previous` désigne la même image que `prod` (comportement inchangé, un seul niveau).

## Recommandation pour la suite
- Relancer `make test-only` là où la base est joignable (ou démarrer le service `postgres`).
- Sur la VM : `deploy/deploy.sh --dry-run` (doit afficher les deux `docker tag` par nom, ou le message
  « aucun retour arrière possible ») puis le déploiement réel ; vérifier ensuite `docker image ls`
  (`prod`, `prod-previous`, `prod-service`) et un `--rollback --dry-run`.
