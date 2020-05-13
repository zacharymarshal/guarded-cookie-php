.PHONY: build phpfmt tests

build:
	docker-compose build
	docker-compose up -d
	docker-compose exec php-fpm composer install

phpfmt:
	docker-compose exec php-fpm /usr/local/bin/php-cs-fixer fix

phpfmt-check:
	docker-compose exec php-fpm /usr/local/bin/php-cs-fixer fix --dry-run \
		--stop-on-violation --using-cache=no

lint:
	docker-compose exec php-fpm bin/lint

tests: phpfmt-check lint
	docker-compose exec php-fpm /usr/local/bin/phpunit

watch-tests:
	docker-compose exec php-fpm bin/watch-tests
