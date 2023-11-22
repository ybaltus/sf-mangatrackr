#=================Variables======================================#
#--- PHP CS FIXER
PHPCSFIXER = php vendor/friendsofphp/php-cs-fixer/php-cs-fixer

#--- PHPUnit
PHPUNIT = php bin/phpunit

#--- Composer
COMPOSER = composer
COMPOSER_INSTALL = $(COMPOSER) install
COMPOSER_UPDATE = $(COMPOSER) update

#--- Symfony cli
SYMFONY = symfony
SYMFONY_CONSOLE = $(SYMFONY) console
SYMFONY_SERVER_START = $(SYMFONY) server:start -d
SYMFONY_SERVER_STOP = $(SYMFONY) server:stop

#--- NPM
NPM = npm
NPM_INSTALL = $(NPM) install --force
NPM_UPDATE = $(NPM) update

#=================Commands========================================#

##-----------------🐛 Quality code 🐛-------------#
qa-cs-fixer-dry-run-verbose: ## Run php-cs-fixer with dry run and verbose
	$(PHPCSFIXER) fix src --dry-run --verbose

qa-cs-fixer-dry-run-diff: ## Run php-cs-fixer with dry run and diff
	$(PHPCSFIXER) fix src --dry-run --diff

qa-cs-fixer: ## Run php-cs-fixer
	$(PHPCSFIXER) fix src --verbose

##----------------- ✅ Execute tests ✅ -------------#
unit-tests:## Run PHPUnit
	$(PHPUNIT) --testdox

##----------------- 📦️ Composer 📦️ -------------#
composer-install: ## Install composer dependencies.
	$(COMPOSER_INSTALL)

composer-update: ## Update composer dependencies.
	$(COMPOSER_UPDATE)

##----------------- 📦️ NPM 📦️ -------------#
npm-install: ## Install npm dependencies.
	$(NPM_INSTALL)

npm-update: ## Update npm dependencies.
	$(NPM_UPDATE)

npm-build: ## Build assets.
	$(NPM) run build

npm-dev: ## Build assets in dev mode.
	$(NPM) run dev

npm-watch: ## Watch assets.
	$(NPM) run watch

##----------------- 🧑‍💻 SYMFONY 🧑‍💻 -------------#
sf-start: ## Start symfony server.
	$(SYMFONY_SERVER_START)

sf-stop: ## Stop symfony server.
	$(SYMFONY_SERVER_STOP)

sf-cc: ## Clear symfony cache.
	$(SYMFONY_CONSOLE) cache:clear

sf-log: ## Show symfony logs.
	$(SYMFONY) server:log

sf-ddc: ## Create symfony database.
	$(SYMFONY_CONSOLE) doctrine:database:create --if-not-exists

sf-ddd: ## Drop symfony database.
	$(SYMFONY_CONSOLE) doctrine:database:drop --if-exists --force

sf-dmm: ## Update symfony schema database.
	$(SYMFONY_CONSOLE) doctrine:migrations:migrate --no-interaction

sf-dfl: ## Execute doctrine fixtures.
	$(SYMFONY_CONSOLE) doctrine:fixtures:load --no-interaction

sf-mm: ## Make migrations.
	$(SYMFONY_CONSOLE) make:migration

##----------------- 🎉 First install 🎉 -------------#
first-install: composer-install npm-install npm-build sf-ddc sf-dmm ## First installation

##----------------- 🆘  HELP 🆘  -------------#
help: ## Show this help.
	@echo "Mangatracker Makefile"
	@echo "---------------------------"
	@echo "Usage: make [target]"
	@echo ""
	@echo "Targets:"
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'
#---------------------------------------------#

