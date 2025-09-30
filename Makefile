.PHONY: setup up down migrate seed test key:gen jwt:generate jwt:rotate tinker

setup:
\tdocker compose build
\tdocker compose up -d
\t@echo "Installing composer deps..."
\tdocker compose exec app composer install
\tmake key:gen
\tmake migrate
\tmake seed
\tmake jwt:generate

up:
\tdocker compose up -d

down:
\tdocker compose down -v

migrate:
\tdocker compose exec app php artisan migrate

seed:
\tdocker compose exec app php artisan db:seed

key:gen:
\tdocker compose exec app php artisan key:generate

jwt:generate:
\tdocker compose exec app php artisan jwt:keys:generate --activate

jwt:rotate:
\tdocker compose exec app php artisan jwt:keys:rotate --grace-days=1

test:
\tdocker compose exec app php artisan test --testsuite=Feature
