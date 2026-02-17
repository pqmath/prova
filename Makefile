.PHONY: up down restart setup test-api test-terceiro test-all bash-api bash-terceiro help

help:
	@echo ""
	@echo "🔥 Bombeiros System Automation 🔥"
	@echo ""
	@echo "Usage:"
	@echo "  make up             Start all containers (API + Terceiro + Workers + Simulator)"
	@echo "  make down           Stop and remove all containers"
	@echo "  make restart        Restart all containers"
	@echo "  make setup          Run setup scripts (Composer + Migrations + NPM) for BOTH systems"
	@echo "  make test-api       Run tests for API Desafio (with coverage)"
	@echo "  make test-terceiro  Run tests for Sistema Terceiro (with coverage)"
	@echo "  make test-all       Run tests for BOTH systems"
	@echo "  make bash-api       Enter API Desafio container shell"
	@echo ""

up:
	docker-compose up -d --build

down:
	docker-compose down

restart: down up

setup:
	@echo "🚀 Setting up API Desafio..."
	docker exec -it bombeiros-api-desafio composer run setup
	@echo ""
	@echo "🚀 Setting up Sistema Terceiro..."
	docker exec -it bombeiros-sistema-terceiro composer run setup
	@echo ""
	@echo "✅ Setup complete! Access endpoints:"
	@echo "   - API Desafio: http://localhost:8001"
	@echo "   - Sistema Terceiro: http://localhost:8000"

test-api:
	@echo "🧪 Running API Desafio Tests..."
	docker exec -it -e XDEBUG_MODE=coverage bombeiros-api-desafio vendor/bin/phpunit --coverage-text

test-terceiro:
	@echo "🧪 Running Sistema Terceiro Tests..."
	docker exec -it -e XDEBUG_MODE=coverage bombeiros-sistema-terceiro vendor/bin/phpunit --coverage-text

test-all: test-api test-terceiro

bash-api:
	docker exec -it bombeiros-api-desafio bash

bash-terceiro:
	docker exec -it bombeiros-sistema-terceiro bash
