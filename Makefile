#!/usr/bin/make -f

.DEFAULT_GOAL := help
SHELL = /bin/bash -o pipefail

cwd := $(shell pwd)

PHP_VERSION := 8.2
TTY_FLAG = $(shell test -z $(CI) && echo "-t")
TMP_DIR = $(cwd)/build
COMPOSER_HOME := $$HOME/.composer
#DOCKER_TOOLS_IMG = composer:lts
DOCKER_TOOLS_IMG = php
SYMFONY_CMD = docker compose exec php bin/console

.PHONY: dev-init
dev-init:  docker.up composer-install-dev								## init dev environment
	$(call run_in_tools, bin/console lexik:jwt:generate-keypair --skip-if-exists)
	$(SYMFONY_CMD) doctrine:database:drop --force || true
	$(SYMFONY_CMD) doctrine:database:create
	##$(SYMFONY_CMD) doctrine:migrations:migrate -n
	$(MAKE) fixtures

.PHONY: docker.up
docker.up:																## start docker stack
	docker compose up --force-recreate -d
	docker compose ps

.PHONY: docker.down
docker.down:															## stop docker stack
	docker compose down -t0 --volumes

.PHONY: shell
shell:
	docker compose exec -uwww-data php bash

.PHONY: fixtures
fixtures:																## exec doctrine fixtures
	$(call run_in_tools, bin/console doctrine:fixtures:load --append)

.PHONY: composer-install-dev
composer-install-dev:													## install composer deps
	$(call run_in_tools, composer i --ignore-platform-reqs)

.PHONY: test
test: lint phpcs phpstan phpunit										## run all tests

.PHONY: lint
lint:																	## run linters
	$(call run_in_tools, vendor/bin/phplint ./ -c phplint.yml)
	$(call run_in_tools, bin/console lint:twig templates -v)
	$(call run_in_tools, bin/console lint:yaml config/*.yaml --parse-tags -v)

.PHONY: phpcs
phpcs:																	## run phpcs
	$(call run_in_tools, vendor/bin/phpcs)

.PHONY: phpstan
phpstan:																## run phpstan
	$(call run_in_tools, APP_ENV=test vendor/bin/phpstan analyze -c phpstan.dist.neon --memory-limit=1G --no-ansi)

.PHONY: phpunit
phpunit:																## run phpunit
	$(call run_in_tools, bin/phpunit --testdox) ## todo: use phpunit image

.PHONY: phpcbf
phpcbf:																	## run phpcbf
	$(call run_in_tools, vendor/bin/phpcbf)

ERD_BIN := $(shell command -v erd-go 2> /dev/null)
ERD_DIR := './docs/erm'
ERD_FILES := $(notdir $(shell find $(ERD_DIR)/*.er -type f))

.PHONY: build-erd
build-erd: 																## buid erd diagrams from .er files
	@echo "Generating SVGs from .er files..."
ifndef ERD_BIN
	$(error "erd or erd-go is required to run this target. Please install")
endif
	@for file in $(ERD_FILES); do \
		$(ERD_BIN) -i $(ERD_DIR)/$$file | dot -Tsvg -o $(ERD_DIR)/$$file.svg; \
	done;
	@echo "Done."

.PHONY: help
help:																	## show this help
	@IFS=$$'\n' ; \
		help_lines=(`fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//'`); \
		for help_line in $${help_lines[@]}; do \
				IFS=$$'#' ; \
				help_split=($$help_line) ; \
				help_command=`echo $${help_split[0]} | sed -e 's/^ *//' -e 's/ *$$//'` ; \
				help_info=`echo $${help_split[2]} | sed -e 's/^ *//' -e 's/ *$$//'` ; \
				printf "  \033[32m%-15s\033[0m %s\n" $$help_command $$help_info ; \
		done

$(TMP_DIR):
	mkdir -p $(TMP_DIR) $(TMP_DIR)/log

define run_in_tools
	docker compose run -i $(TTY_FLAG) --rm $(2) $(DOCKER_TOOLS_IMG) /bin/bash -c "$(1)"
endef

