backend-dir-path := $(CURDIR)/backend
frontend-dir-path := $(CURDIR)/frontend

# Default target
all: targets

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
	bin/test $(backend-dir-path)

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
	sass --watch $(frontend-dir-path)/localhost

clean-css:
	find $(frontend-dir-path)/localhost -mindepth 1 \( -name '*.css' -or -name '*.css.map' -not -path '*/node_modules/*' \) -print -delete

clean-js:
	find $(frontend-dir-path)/localhost -mindepth 1 -not -path '*/node_modules/*' -and \( -name '*.js' -or -name '*.js.map' -or -name '*.tsbuildinfo' -or -name '*.d.ts' \) -not -path '*/lib/base/index.d.ts' -print -delete

clean-assets: clean-css clean-js

###############################################################################
# Docker

build:
	docker-compose build

################################################################################

clean: clean-assets
	sudo sh -c 'rm -rf test/Integration/*.log $(backend-dir-path)/localhost/{log,cache}/*'

clean-routes:
	sudo sh -c 'rm -rfv $(backend-dir-path)/localhost/cache/router'

update: update-peg
	composer update
	# We use `install` instead of `update` to run the [scripts](https://docs.npmjs.com/misc/scripts#description) defined in the package.json file.
	cd $(frontend-dir-path) && npm install

update-peg:
	curl -fLo $(CURDIR)/lib/Tech/Python/python.token 'https://raw.githubusercontent.com/python/cpython/main/Grammar/Tokens'
	curl -fLo $(CURDIR)/lib/Tech/Python/python.gram 'https://raw.githubusercontent.com/python/cpython/main/Grammar/python.gram'
	curl -fLo $(CURDIR)/lib/Compiler/Frontend/Peg/meta.gram 'https://raw.githubusercontent.com/python/cpython/main/Tools/peg_generator/pegen/metagrammar.gram'
	target_file_path=$(CURDIR)/test/Unit/Compiler/Frontend/Peg/test-data/GeneralTokenizerTest/meta-token \
		&& cat $(CURDIR)/lib/Compiler/Frontend/Peg/meta.gram | $(CURDIR)/bin/gen-py-tokens > "$$target_file_path" \
		&& echo "Written '$$target_file_path'"

init:
	composer require --dev psalm/plugin-phpunit && vendor/bin/psalm-plugin enable psalm/plugin-phpunit
	test -e package.json || echo '{}' > package.json
	npm install -g --save-dev @types/node
	npm install -g --save typescript@next concurrently

###############################################################################
# Help

# `help` taken from [containerd](https://github.com/containerd/containerd/blob/master/Makefile)
help: ## This help
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

targets: ## Show available targets
	echo Targets:
	grep -oP '^[A-Za-z0-9_-]+:' $(MAKEFILE_LIST) | awk -F':' '{print $$(NF-1)}' | perl -WpE 's/^/    /g'

###############################################################################
# `make` tweaks

unexport _JAVA_OPTIONS

.PHONY: all test unit-test integration-test backend-test module-test frontend-test lint assets js watch-js css watch-css clean-css clean-js clean-assets build clean clean-routes update update-peg init help targets
.SILENT:
# Do not use make's built-in rules and variables (this increases performance and avoids hard-to-debug behaviour).
MAKEFLAGS += -rR
# Warning on undefined variables.
MAKEFLAGS += --warn-undefined-variables
# Suppress "Entering directory ..." unless we are changing the work directory.
MAKEFLAGS += --no-print-directory
# Use bash as SHELL in recipes
SHELL := /bin/bash

###############################################################################

.PHONY: all test unit-test integration-test backend-test module-test frontend-test lint assets js watch-js css watch-css clean-css clean-js clean-assets build clean clean-routes update update-peg init help targets

define dl
	curl -sSfL $(1) -o $(2)
endef
