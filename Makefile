all: lint static-analysis unit-test check-coverage build-dev functional-test build scan

lint: copy-env
	docker compose run lint

static-analysis phpstan: copy-env composer-install
	docker compose run phpstan

composer-install:
	composer install -n --prefer-dist

unit-test: composer-install copy-env
	docker compose --project-name notify-queue-consumer run --rm test

check-coverage: copy-env
	php -f scripts/coverage-checker.php test-results/clover/results.xml 100

DEV_DEPS:="false"

build-dev: DEV_DEPS="true"

build build-dev: copy-env
	docker compose build consumer --build-arg ENABLE_DEV_DEPS=$(DEV_DEPS)

copy-env:
	cp local.env.example local.env

functional-test: copy-env build-dev
	docker compose up --wait -d localstack
	docker compose up --wait --build --force-recreate -d mock-notify-ci
	docker compose up --wait --build --force-recreate -d mock-sirius-ci
	docker compose run test-functional-ci
	docker compose down

phpmetrics: copy-env
	docker compose run phpmetrics

scan:
	trivy image --exit-code 1 notify-queue-consumer:latest

up: copy-env
	docker compose up --wait -d localstack
	docker compose up --wait consumer
