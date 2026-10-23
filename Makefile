all: lint static-analysis unit-test check-coverage functional-test build down

test-results:
	mkdir -p -m 0777 test-results

setup-directories: test-results

composer-install:
	docker compose run composer

lint:
	docker compose run \
	--volume ${PWD}/build/output/:/output/ \
	--rm consumer-test \
	vendor/bin/phpcs \
		-p --report=checkstyle --standard=PSR12 \
		--warning-severity=0 ./src ./public

static-analysis phpstan:
	docker compose run \
	--volume ${PWD}/build/output/:/output/ \
	--rm consumer-test \
	vendor/bin/phpstan \
		analyse -c /var/www/tests/phpstan.neon

unit-test: create-output-dir
		docker compose run \
		--volume ${PWD}/build/output/:/output/ \
		--rm consumer-test \
		vendor/bin/phpunit \
			--configuration=/var/www/tests/phpunit.xml \
			--coverage-text=/output/unit-coverage.txt \
			--log-junit=/output/unit-test.xml \
			--testsuite=unit

build:
	docker compose build consumer

copy-env:
	cp local.env.example local.env

create-output-dir:
	mkdir -p -m 0777 build/output

functional-test: create-output-dir
	docker compose up --wait -d localstack
	docker compose up --wait --build --force-recreate -d mock-sirius
	docker compose up --wait --build --force-recreate -d mock-notify
	docker compose run \
		--volume ${PWD}/build/output/:/output/ \
		--env-from-file local.env.example \
		--rm consumer-test \
		vendor/bin/phpunit \
			--configuration=/var/www/tests/phpunit.xml \
			--no-coverage \
			--log-junit=/output/functional-test.xml \
			--testsuite=functional
	docker compose down

up: copy-env
	docker compose up --wait consumer

down:
	docker compose down
