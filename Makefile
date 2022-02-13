backendDirPath := $(CURDIR)/backend
frontendDirPath := $(CURDIR)/frontend

# Default target
all: help

################################################################################
# Tests

test:
	bin/test

# Unit tests
unit-test:
	bin/test test/Unit/TestSuite.php

integration-test:
	bin/test test/Integration/TestSuite.php

backend-test: module-test
module-test:
	bin/test $(backendDirPath)

# todo: frontend tests
frontend-test:
	echo todo
	exit 1

lint:
	php test/lint.php

###############################################################################
# Assets

assets: js css

js:
	bin/js build

watch-js:
	bin/js watch

css:
	bin/css build

watch-css:
	sass --watch $(frontendDirPath)/localhost

clean-css:
	find $(frontendDirPath)/localhost -mindepth 1 \( -name '*.css' -or -name '*.css.map' -not -path '*/node_modules/*' \) -print -delete

clean-js:
	find $(frontendDirPath)/localhost -mindepth 1 -not -path '*/node_modules/*' -and \( -name '*.js' -or -name '*.js.map' -or -name '*.tsbuildinfo' -or -name '*.d.ts' \) -not -path '*/lib/base/index.d.ts' -print -delete

clean-assets: clean-css clean-js

###############################################################################
# Docker

build:
	docker-compose build

################################################################################

clean: clean-assets
	sudo sh -c 'rm -rf test/Integration/*.log $(backendDirPath)/localhost/{log,cache}/*'

clean-routes:
	sudo sh -c 'rm -rfv $(backendDirPath)/localhost/cache/router'

update:
	composer update
	# We use `install` instead of `update` to run the [scripts](https://docs.npmjs.com/misc/scripts#description) defined in the package.json file.
	cd $(frontendDirPath) && npm install

init:
	composer require --dev psalm/plugin-phpunit && vendor/bin/psalm-plugin enable psalm/plugin-phpunit
	test -e package.json || echo '{}' > package.json
	npm install -g --save-dev @types/node
	npm install -g --save typescript@next concurrently

pull-peg:
	curl -L 'https://raw.githubusercontent.com/python/cpython/main/Grammar/Tokens' -o $(CURDIR)/lib/Tech/Python/Tokens
	curl -L 'https://raw.githubusercontent.com/python/cpython/main/Grammar/python.gram' -o $(CURDIR)/lib/Tech/Python/python.peg
	curl -L 'https://raw.githubusercontent.com/python/cpython/main/Tools/peg_generator/pegen/metagrammar.gram' -o $(CURDIR)/lib/Compiler/Frontend/Peg/peg.peg
	mkdir -p $(CURDIR)/test/Unit/Compiler/Frontend/Peg/test-data
	cp $(CURDIR)/lib/Tech/Python/python.peg $(CURDIR)/test/Unit/Compiler/Frontend/Peg/test-data
	cp $(CURDIR)/lib/Compiler/Frontend/Peg/peg.peg $(CURDIR)/test/Unit/Compiler/Frontend/Peg/test-data

###############################################################################
# Help

# `help` taken from [containerd](https://github.com/containerd/containerd/blob/master/Makefile)
help: ## This help
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

targets:
	echo Targets:
	grep -oP '^[A-Za-z0-9_-]+:' $(CURDIR)/$(MAKEFILE_LIST) | cut -d: -f2 | perl -WpE 's/^/  /g'

###############################################################################
# make tweaks

.SILENT:
# Do not use make's built-in rules and variables (this increases performance and avoids hard-to-debug behaviour).
MAKEFLAGS += -rR
# Warning on undefined variables.
MAKEFLAGS += --warn-undefined-variables
# Suppress "Entering directory ..." unless we are changing the work directory.
MAKEFLAGS += --no-print-directory
# Enable Bash features like brace expansion
SHELL := /bin/bash

###############################################################################

.PHONY: all test unit-test integration-test backend-test module-test frontend-test lint assets js watch-js css watch-css clean-css clean-js clean-assets build clean clean-routes update init pull-peg help targets

define dl
	curl -sSfL $(1) -o $(2)
endef
