all: lint static-analysis unit-test check-coverage build-dev functional-test build scan down

lint: copy-env
	docker compose run lint

test-results:
	mkdir -p -m 0777 test-results .trivy-cache

setup-directories: test-results

static-analysis phpstan: copy-env composer-install
	docker compose run phpstan

composer-install:
	docker compose run composer

unit-test: composer-install copy-env
	docker compose --project-name notify-queue-consumer run --rm test

check-coverage: copy-env
	docker compose run check-coverage

DEV_DEPS:="false"

build-dev: DEV_DEPS="true"

build build-dev: copy-env
	docker compose build consumer --build-arg ENABLE_DEV_DEPS=$(DEV_DEPS)

copy-env:
	cp local.env.example local.env

functional-test: copy-env build-dev
	docker compose up --wait -d localstack
	docker compose up --wait --build --force-recreate -d mock-sirius
	docker compose up --wait --build --force-recreate -d mock-notify
	docker compose run test-functional
	docker compose down

phpmetrics: copy-env
	docker compose run phpmetrics

scan: setup-directories copy-env
	docker compose run --rm trivy image --format table --exit-code 0 311462405659.dkr.ecr.eu-west-1.amazonaws.com/sirius/notify-queue-consumer:latest
	docker compose run --rm trivy image --format sarif --output /test-results/trivy.sarif --exit-code 1 311462405659.dkr.ecr.eu-west-1.amazonaws.com/sirius/notify-queue-consumer:latest

up: copy-env
	docker compose up --wait consumer

down:
	docker compose down
