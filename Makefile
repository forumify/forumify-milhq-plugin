.PHONY: quality
quality:
	@./vendor/bin/phpcs -s -p
	@./vendor/bin/phpstan

.PHONY: quality-fix
quality-fix:
	@./vendor/bin/phpcbf

.PHONY: tests
tests:
	make setup-tests
	make run-tests

.PHONY: setup-tests
setup-tests:
	rm -rf tests/var
	@cd tests && composer update
	@cd tests && php bin/console doctrine:database:drop --if-exists --force --env=test
	@cd tests && php bin/console doctrine:database:create --env=test
	@cd tests && php bin/console doctrine:migrations:migrate --no-interaction --env=test
	@cd tests && php bin/console forumify:platform:setting -k forumify.platform_installed --value true
	@cd tests && php bin/console forumify:plugins:refresh --env=test
	@cd tests && php bin/console forumify:plugins:activate forumify/forumify-milhq-plugin --env=test

.PHONY: run-tests
run-tests:
	@cd tests && ./vendor/bin/phpunit -c phpunit.xml.dist
