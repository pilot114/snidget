# snidget: учебный PHP-микрофреймворк (DDD + CQRS)
# Цели одинаковые во всех проектах ~/projects — см. make help.
# Всё выполняется в docker (php 8.6): на хосте php не нужен.
.DEFAULT_GOAL := help

# файлы в /app создаются от имени хоста
export PUID := $(shell id -u)
export PGID := $(shell id -g)

include dev/docker/.env

COMPOSE := docker compose -f dev/docker/serve.yml
RUN     := $(COMPOSE) run --rm php

.PHONY: help
help: ## эта справка
	@grep -hE '^[a-zA-Z0-9_-]+:.*## ' $(MAKEFILE_LIST) | awk -F':.*## ' '{printf "  %-12s %s\n", $$1, $$2}'

.PHONY: install
install: ## поставить зависимости
	$(RUN) composer install

.PHONY: dev
dev: ## запустить для разработки: nginx + php-fpm (порт — PORT в dev/docker/.env), Ctrl+C — стоп
	$(COMPOSE) up

.PHONY: docs
docs: ## собрать документацию (dev/docs/md → dev/docs/index.html)
	$(RUN) php bin/docs
	@echo "документация: http://localhost:$(PORT)/docs/ (если не открывается — make dev)"

.PHONY: enter
enter: ## shell в php-контейнере (composer test | check | fix)
	$(RUN) zsh

.PHONY: build
build: ## сборка docker-образов
	$(COMPOSE) build

.PHONY: audit
audit: ## уязвимости в зависимостях
	$(RUN) composer audit

.PHONY: outdated
outdated: ## устаревшие зависимости
	$(RUN) composer outdated --direct
