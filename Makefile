COMPOSE=docker-compose
PHP=$(COMPOSE) exec php
CONSOLE=$(PHP) bin/console
COMPOSER=$(PHP) composer

up:
	mkdir -p ${HOME}/.composer
	chmod -R ugo+rwx ${HOME}/.composer
	@${COMPOSE} up

down:
	@${COMPOSE} down --volumes

clear:
	@${CONSOLE} cache:clear

migration:
	@${CONSOLE} make:migration

migrate:
	@${CONSOLE} doctrine:migrations:migrate

fixtload:
	@${CONSOLE} doctrine:fixtures:load

require:
	@${COMPOSER} require $2

phpunit:
	@${PHP} bin/phpunit

rebuild:
	$(COMPOSE) up --build