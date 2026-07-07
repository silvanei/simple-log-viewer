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

down:
	docker compose down

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

build-production:
	docker build --target production \
		-t silvanei/simple-log-viewer:$(VERSION) \
		-t silvanei/simple-log-viewer:latest .

changelog:
	@echo "Generating changelog for v$(VERSION)..."
	@command -v git-cliff >/dev/null 2>&1 && \
		git cliff --tag v$(VERSION) --unreleased || \
		docker run --rm -v $(PWD):/workspace -w /workspace \
			orhun/git-cliff:latest \
			git cliff --tag v$(VERSION) --unreleased
