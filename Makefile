#=================Variables======================================#

##--- PHP CS Fixer
PHPCSFIXER = php vendor/friendsofphp/php-cs-fixer/php-cs-fixer

##--- PHPUnit
PHPUNIT = php bin/phpunit

#=================Commands========================================#

#-----------------🐛 Quality code 🐛-------------#

##--- Run php-cs-fixer with dry run
qa-cs-fixer-dry-run-verbose:
	$(PHPCSFIXER) fix src --dry-run --verbose

##--- Run php-cs-fixer with dry run
qa-cs-fixer-dry-run-diff:
	$(PHPCSFIXER) fix src --dry-run --diff

##--- Run php-cs-fixer
qa-cs-fixer:
	$(PHPCSFIXER) fix src --verbose

#----------------- ✅ Execute tests ✅-------------#
##-- Run PHPUnit
unit-tests:
	$(PHPUNIT) --testdox
