run: ## Run the consumer
	php -f public/consumer.php

test: ## Run the tests
	./vendor/bin/phpunit -c phpunit.xml