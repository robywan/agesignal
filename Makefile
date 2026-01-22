.PHONY: init default

help:
	@echo "Makefile targets:"
	@echo "  init      - Initialize a new Laravel project in the current directory using DDEV."

init:
	ddev config --project-type=laravel --docroot=public --php-version=8.4
	ddev start
	ddev exec laravel new temp --database=sqlite --boost
	ddev exec 'rsync -rltgopD temp/ ./ && rm -rf temp'
	ddev restart
	ddev composer run-script post-root-package-install
	ddev composer run-script post-create-project-cmd