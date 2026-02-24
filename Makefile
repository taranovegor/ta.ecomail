# Default environment (dev or prod). Prod environment has not been tested.
ENV ?= dev

ifeq ($(shell command -v docker-compose 2> /dev/null),)
	DOCKER_COMPOSE_CMD = docker compose
else
	DOCKER_COMPOSE_CMD = docker-compose
endif

COMPOSE_DEV = $(DOCKER_COMPOSE_CMD) -f compose.dev.yaml
COMPOSE_PROD = $(DOCKER_COMPOSE_CMD) -f compose.prod.yaml

ifeq ($(ENV),prod)
	COMPOSE = $(COMPOSE_PROD)
else
	COMPOSE = $(COMPOSE_DEV)
endif

.PHONY: help

help: ## Displays help for a command
	@printf "\033[33mUsage:\033[0m\n  make [options] [target] ...\n\n\033[33mAvailable targets:%-13s\033[0m\n"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' 'Makefile' | awk 'BEGIN {FS = ":.*?## "}; {printf "%-2s\033[32m%-20s\033[0m %s\n", "", $$1, $$2}'

up: ## Start containers
	$(COMPOSE) up -d

down: ## Stop containers
	$(COMPOSE) down

build: ## Build containers
	$(COMPOSE) build

rebuild: ## Rebuild containers without cache
	$(COMPOSE) build --no-cache

logs: ## Show logs
	$(COMPOSE) logs -f

ps: ## Show running containers
	$(COMPOSE) ps -a

bash: ## Enter workspace container
	$(COMPOSE) exec workspace bash

artisan: ## Run artisan command (use ARGS='...')
	$(COMPOSE) exec workspace php artisan $(ARGS)

composer: ## Run composer command (use ARGS='...')
	$(COMPOSE) exec workspace composer $(ARGS)

npm: ## Run npm command (use ARGS='...')
	$(COMPOSE) exec workspace npm $(ARGS)

migrate: ## Run migrations
	$(COMPOSE) exec workspace php artisan migrate

fresh: ## Fresh migrate with seed
	$(COMPOSE) exec workspace php artisan migrate:fresh --seed

seed: ## Run db seed
	$(COMPOSE) exec workspace php artisan db:seed

phpstan: ## Run PHPStan static analysis
	$(COMPOSE) exec workspace ./vendor/bin/phpstan analyse

pint: ## Run Laravel Pint code formatter
	$(COMPOSE) exec workspace ./vendor/bin/pint

pint-check: ## Check code style with Laravel Pint (without changes)
	$(COMPOSE) exec workspace ./vendor/bin/pint --test

format: ## Format code with Pint and PHPCS
	$(COMPOSE) exec workspace ./vendor/bin/pint

lint: ## Run all checks (PHPStan, Pint, PHPCS)
	$(COMPOSE) exec workspace ./vendor/bin/phpstan analyse && \
	$(COMPOSE) exec workspace ./vendor/bin/pint --test

test: ## Run tests
	$(COMPOSE) exec workspace composer test

install: ## Full environment setup (build, up, composer setup)
	[ -f .env ] || cp .env.example .env
	$(COMPOSE) build
	$(COMPOSE) up -d
	$(COMPOSE) exec workspace composer setup
\
