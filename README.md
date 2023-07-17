[![ministryofjustice](https://circleci.com/gh/ministryofjustice/opg-notify-queue-consumer.svg?style=svg)](https://github.com/ministryofjustice/opg-notify-queue-consumer)

Queue consumer; messages represent a PDF letter to be sent to the Notify (Notifications) API for printing,
updates Sirius with status.

### Building

    # Install dependencies on your host machine
    make composer-install

    # Update the local.env file with any secret credentials when testing external services
    make build

### Running

    make up

If you are not developing against a local or test version of Notify or Sirius you can run the mock services with:

    docker compose --project-name notify-queue-consumer up -d --build --force-recreate consumer-mock-notify
    docker compose --project-name notify-queue-consumer up -d --build --force-recreate consumer-mock-sirius

## Testing

Unit tests

    make unit-test

Functional tests

    # Ensure the consumer is built before attempting to run the functional tests

    make functional-test

### Coverage

See [IDE PHPUnit coverage integration setup](docs/ide-coverage-setup.md)

## Check Linting / Static Analysis

    make lint
    make phpstan

## Updating composer.json dependencies

    docker compose run composer require <PACKAGE>>:<VERSION>

    E.g.
    docker compose run composer require package/name:^1.0

## References

- [Localstack useful commands ](docs/localstack.md)
- https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/welcome.html
- https://docs.notifications.service.gov.uk/php.html#send-a-precompiled-letter
- http://docs.guzzlephp.org/en/stable/
