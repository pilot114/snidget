# snidget: учебный PHP-микрофреймворк (DDD + CQRS)
# Цели одинаковые во всех проектах ~/projects — см. make help.
.DEFAULT_GOAL := help

.PHONY: help
help: ## эта справка
	@grep -hE '^[a-zA-Z0-9_-]+:.*## ' $(MAKEFILE_LIST) | awk -F':.*## ' '{printf "  %-12s %s\n", $$1, $$2}'

.PHONY: install
install: ## поставить зависимости
	composer install

.PHONY: dev
dev: ## запустить для разработки
	@echo "запускать нечего: это библиотека"

.PHONY: build
build: ## сборка
	@echo "сборки нет"

.PHONY: audit
audit: ## уязвимости в зависимостях
	composer audit

.PHONY: outdated
outdated: ## устаревшие зависимости
	composer outdated --direct
