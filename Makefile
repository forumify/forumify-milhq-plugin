.PHONY: quality
quality:
	@./vendor/bin/phpcs -s -p
	@./vendor/bin/phpstan
	@npm --prefix=./assets run lint

.PHONY: quality-fix
quality-fix:
	@./vendor/bin/phpcbf

.PHONY: tests
tests:
	make setup-tests
	make run-tests

.PHONY: setup-tests
setup-tests:
	@rm -rf tests/var
	@cd tests && php bin/console forumify:plugins:test-setup --env=test

.PHONY: run-tests
run-tests:
	@./vendor/bin/phpunit
