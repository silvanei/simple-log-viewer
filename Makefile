PROJECT_DIR=$(shell pwd)
USER_ID=$(shell id -u)
USER_GROUP=$(shell id -g)
ITERATIVE=-it
ifdef CI
	ITERATIVE=
endif
DOCKER_CONTAINER_RUN=docker container run \
	$(ITERATIVE) \
	--rm \
	--network host \
	-m 1024m \
	-u $(USER_ID):$(USER_GROUP) \
	-v $(PROJECT_DIR):/app \
	-w /app silvanei/simple-log-viewer:dev

# Auto-discover all docker-compose*.yaml files in config/docker/
# Use reverse order so base (docker-compose.yaml) comes first, overrides come last
COMPOSE_FILES:=$(wildcard config/docker/docker-compose*.yaml)
COMPOSE_FILES_REVERSE:=$(foreach i,$(COMPOSE_FILES),$(word $(words $(COMPOSE_FILES)),$(COMPOSE_FILES)) $(word $(words $(COMPOSE_FILES)),$(COMPOSE_FILES)))
# Simpler: base file first, then any overrides (files containing "override")
COMPOSE_BASE:=$(firstword $(wildcard config/docker/docker-compose.yaml))
COMPOSE_OVERRIDES:=$(filter-out $(COMPOSE_BASE),$(wildcard config/docker/docker-compose*.yaml))
COMPOSE_FLAGS=-f $(COMPOSE_BASE) $(foreach f,$(COMPOSE_OVERRIDES),-f $(f))

# Validate at least base exists
ifeq ($(COMPOSE_BASE),)
$(error No docker-compose.yaml found in config/docker/)
endif

.PHONY: default test
default: image;

image:
	docker build --target development -t silvanei/simple-log-viewer:dev -f config/docker/Dockerfile .

install:
	$(DOCKER_CONTAINER_RUN) composer install

update:
	$(DOCKER_CONTAINER_RUN) composer update

serve:
	docker compose $(COMPOSE_FLAGS) up -d

down:
	docker compose $(COMPOSE_FLAGS) down

sh:
	$(DOCKER_CONTAINER_RUN) sh

test:
	$(DOCKER_CONTAINER_RUN) composer test -- $(args)

phpstan:
	$(DOCKER_CONTAINER_RUN) composer phpstan -- $(args)

phpcs:
	$(DOCKER_CONTAINER_RUN) composer phpcs -- $(args)

infection:
	$(DOCKER_CONTAINER_RUN) composer test-infection -- $(args)

check:
	$(DOCKER_CONTAINER_RUN) composer check
