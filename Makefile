.PHONY: build phpfmt tests

build:
	docker-compose build
	docker-compose up -d
	docker-compose exec php-fpm composer install

phpfmt:
	docker-compose exec php-fpm /usr/local/bin/php-cs-fixer fix

tests:
	docker-compose exec php-fpm /usr/local/bin/php-cs-fixer fix --dry-run \
		--stop-on-violation --using-cache=no
	docker-compose exec php-fpm bin/lint
