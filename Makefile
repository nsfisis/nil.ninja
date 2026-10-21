.PHONY: all
all: deploy

.PHONY: deploy
deploy: clean build serve

.PHONY: provision
provision:
	sudo sh provisioning/run.sh

.PHONY: build
build:
	cd services/albatross-php-2024;   make -f Makefile.prod build
	cd services/nilink;               make -f Makefile.prod build
	cd services/albatross-php-2026;   make -f Makefile.prod build
	cd vhosts/t/albatross-swift;      make -f Makefile.prod build
	cd vhosts/t/albatross-php-2025;   make -f Makefile.prod build
	cd vhosts/t/albatross-swift-2025; make -f Makefile.prod build
	cd vhosts/t/phpcon-kagawa-2025;   make -f Makefile.prod build

.PHONY: serve
serve:
	sudo systemctl start mioproxy
	cd services/albatross-php-2024;   make -f Makefile.prod serve
	cd services/nilink;               make -f Makefile.prod serve
	cd services/albatross-php-2026;   make -f Makefile.prod serve
	cd vhosts/t/albatross-swift;      make -f Makefile.prod serve
	cd vhosts/t/albatross-php-2025;   make -f Makefile.prod serve
	cd vhosts/t/albatross-swift-2025; make -f Makefile.prod serve
	cd vhosts/t/phpcon-kagawa-2025;   make -f Makefile.prod serve

.PHONY: clean
clean:
	cd vhosts/t/phpcon-kagawa-2025;   make -f Makefile.prod clean
	cd vhosts/t/albatross-swift-2025; make -f Makefile.prod clean
	cd vhosts/t/albatross-php-2025;   make -f Makefile.prod clean
	cd vhosts/t/albatross-swift;      make -f Makefile.prod clean
	cd services/albatross-php-2026;   make -f Makefile.prod clean
	cd services/nilink;               make -f Makefile.prod clean
	cd services/albatross-php-2024;   make -f Makefile.prod clean
	sudo systemctl stop mioproxy
