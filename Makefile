.PHONY: build

build:
	docker-compose build
	docker-compose up -d
	docker-compose exec php-fpm composer install
