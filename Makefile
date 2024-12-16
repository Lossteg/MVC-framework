DOCKER_COMPOSE_FILE := ./docker-compose.yml

install:
	docker compose -f $(DOCKER_COMPOSE_FILE) up --build -d

start:
	docker compose -f $(DOCKER_COMPOSE_FILE) up -d

stop:
	docker compose -f $(DOCKER_COMPOSE_FILE) down

# Вход в контейнер PHP в интерактивном режиме
terminal:
	docker compose -f $(DOCKER_COMPOSE_FILE) exec php sh

# Правило .PHONY для обозначения целей, не являющихся файлами
.PHONY: install start stop terminal