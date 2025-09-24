.PHONY: up down restart app bash migrate composer

up:
	docker-compose up -d --build

down:
	docker-compose down

restart: down up

bash:
	docker-compose exec app bash

migrate:
	docker-compose exec app php artisan migrate

composer:
	docker-compose exec app composer install
