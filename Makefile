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

.PHONY: default test
default: image;

image:
	docker build --target development -t silvanei/simple-log-viewer:dev .

install:
	$(DOCKER_CONTAINER_RUN) composer install

update:
	$(DOCKER_CONTAINER_RUN) composer update

serve:
	docker compose up -d

serve-watch:
	./bin/watch

down:
	docker compose down

sh:
	$(DOCKER_CONTAINER_RUN) sh

test:
	$(DOCKER_CONTAINER_RUN) composer test -- $(args)

phpstan:
	$(DOCKER_CONTAINER_RUN) composer phpstan

phpcs:
	$(DOCKER_CONTAINER_RUN) composer phpcs

check:
	$(DOCKER_CONTAINER_RUN) composer check
