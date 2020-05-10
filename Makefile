.PHONY: build phpfmt

build:
	docker-compose build
	docker-compose up -d
	docker-compose exec php-fpm composer install

phpfmt:
	docker-compose exec php-fpm /usr/local/bin/php-cs-fixer fix
