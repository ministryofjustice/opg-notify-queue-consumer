run: ## Run the consumer
	php -f public/consumer.php

ARGS ?= ""
test: ## Run the tests
	docker-compose --project-name consumer-test \
		-f docker-compose.yml \
		run \
		--no-deps \
		--volume ${PWD}/build/output:/output \
		--rm consumer \
		vendor/bin/phpunit \
		--configuration=phpunit.xml \
		--testsuite=unit $(ARGS) \
		--log-junit=/output/consumer-unit.xml