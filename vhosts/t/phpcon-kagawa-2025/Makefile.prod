DOCKER_COMPOSE := docker compose

.PHONY: build
build:
	${DOCKER_COMPOSE} build

.PHONY: serve
serve: up

.PHONY: clean
clean: down

.PHONY: up
up:
	${DOCKER_COMPOSE} up -d

.PHONY: down
down:
	${DOCKER_COMPOSE} down --remove-orphans

.PHONY: logs
logs:
	${DOCKER_COMPOSE} logs

.PHONY: logsf
logsf:
	${DOCKER_COMPOSE} logs -f
