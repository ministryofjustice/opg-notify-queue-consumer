[![ministryofjustice](https://circleci.com/gh/ministryofjustice/opg-notify-queue-consumer.svg?style=svg)](https://github.com/ministryofjustice/opg-notify-queue-consumer)

Queue consumer; messages represent a PDF letter to be sent to the Notify (Notifications) API for printing, 
updates Sirius with status.

### Building

    cp local.env.example local.env
    
    # Install dependencies on your host machine
    composer install --prefer-dist --no-interaction --no-scripts
    
    # Update the local.env file with any secret credentials when testing external services
    docker-compose build consumer

### Running

    docker-compose --project-name notify-queue-consumer up localstack
    docker-compose --project-name notify-queue-consumer up consumer
    
If you are not developing against a local or test version of Notify or Sirius you can run the mock services with:

    docker-compose --project-name notify-queue-consumer up -d --build --force-recreate consumer-mock-notify
    docker-compose --project-name notify-queue-consumer up -d --build --force-recreate consumer-mock-sirius

## Testing

Unit tests

    docker-compose --project-name notify-queue-consumer run --rm test

Functional tests
    
    docker-compose --project-name notify-queue-consumer up -d localstack
    docker-compose --project-name notify-queue-consumer up -d --build --force-recreate consumer-mock-notify
    docker-compose --project-name notify-queue-consumer up -d --build --force-recreate consumer-mock-sirius
    docker-compose --project-name notify-queue-consumer run --rm test-functional
    
### Coverage

See [IDE PHPUnit coverage integration setup](docs/ide-coverage-setup.md)    
    
## Check Linting / Static Analysis

    docker-compose --project-name notify-queue-consumer run --rm lint    
    docker-compose --project-name notify-queue-consumer run --rm phpstan
   
## References

- [Localstack useful commands ](docs/localstack.md)
- https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/welcome.html
- https://docs.notifications.service.gov.uk/php.html#send-a-precompiled-letter
- http://docs.guzzlephp.org/en/stable/
