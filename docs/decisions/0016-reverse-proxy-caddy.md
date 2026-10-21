# ADR 0016 : MangaTrackr derrière le reverse proxy Caddy (proxies de confiance)

- Statut : Acceptée
- Date : 2026-10-21
- Contexte lié : ADR 0015, VM `proxy-caddy` (192.168.1.14, Caddy, `mangasync.labytech.fr` vers
  `192.168.1.29:8080`), section 11 de [`apps-dev.md`](../deploiement/apps-dev.md)

## Contexte
Derrière Caddy, l'application ne voit que l'IP du proxy et du HTTP. Sans réglage : liens et
redirections en `http://`, cookies non sécurisés, liens des emails sans domaine, et tous les
visiteurs partagent l'IP 192.168.1.14, donc le `login_throttling` (5 essais / 15 min) bloquerait
tout le monde après les échecs d'une seule personne.

## Décision
- **Symfony** : `framework.trusted_proxies: '%env(TRUSTED_PROXIES)%'` et `trusted_headers`
  `x-forwarded-for/host/proto/port`. Jamais `*` ni `REMOTE_ADDR`. Défaut `.env` : `127.0.0.1` ;
  production : `192.168.1.14` dans le fichier de secrets.
- **URL publique** : `framework.router.default_uri: '%env(DEFAULT_URI)%'` (emails et commandes
  console du worker). Production : `https://mangasync.labytech.fr`.
- **Apache** (`docker/Dockerfile.prod`, `docker/apache/remoteip.conf`) : `mod_remoteip` avec
  `RemoteIPHeader X-Forwarded-For` et `RemoteIPInternalProxy ${TRUSTED_PROXIES}`, donc la même
  variable pour Symfony et Apache (une seule IP ou un CIDR, sans virgule).
- `cookie_secure: auto` est conservé (suit `X-Forwarded-Proto` de confiance) ; `true` seulement
  après validation complète en HTTPS, pour garder l'accès HTTP direct de secours.
- Test `tests/Security/TrustedProxyTest.php` : en-têtes crus depuis le proxy, ignorés sinon.

## Conséquences
- Nouvelles variables obligatoires en production : `TRUSTED_PROXIES`, `DEFAULT_URI`
  (modèle `deploy/mangatrackr.prod.env.dist`). Rebuild de l'image nécessaire (`mod_remoteip`).
- Reste hors dépôt : URI de redirection Google (`https://mangasync.labytech.fr/oauth/callback/google`),
  pare-feu Proxmox de `apps-dev` (8080 limité au proxy et à l'IP d'admin), puis bascule DNS et Livebox.
