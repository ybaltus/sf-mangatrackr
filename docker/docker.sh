make first-install
php bin/console app:init-datas
php bin/console app:create-user demo@demo.fr password Demo 1
exec apache2-foreground