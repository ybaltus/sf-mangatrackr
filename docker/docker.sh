# DEV / DÉMO UNIQUEMENT : ne jamais utiliser en production.
# Ce fichier lance make first-install (migrations), app:init-datas et crée l'utilisateur
# de démonstration demo@demo.fr : il modifierait la base de production.
# Production : docker/Dockerfile.prod + compose.prod.yaml + deploy/deploy.sh.
make first-install
php bin/console app:init-datas
php bin/console app:create-user demo@demo.fr password Demo 1
exec apache2-foreground