#=================Variables======================================#

PHPCSFIXER = php vendor/friendsofphp/php-cs-fixer/php-cs-fixer
PHPUNIT = php bin/phpunit

#=================Commands========================================#

#-----------------🐛 Quality code 🐛-------------#
qa-cs-fixer-dry-run-verbose: ## Run php-cs-fixer with dry run
	$(PHPCSFIXER) fix src --dry-run --verbose

qa-cs-fixer-dry-run-diff: ## Run php-cs-fixer with dry run
	$(PHPCSFIXER) fix src --dry-run --diff

qa-cs-fixer: ## Run php-cs-fixer
	$(PHPCSFIXER) fix src --verbose

#----------------- ✅ Execute tests ✅-------------#
unit-tests:## Run PHPUnit
	$(PHPUNIT) --testdox

## === 🆘  HELP ==================================================
help: ## Show this help.
	@echo "Mangatracker Makefile"
	@echo "---------------------------"
	@echo "Usage: make [target]"
	@echo ""
	@echo "Targets:"
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'
#---------------------------------------------#

