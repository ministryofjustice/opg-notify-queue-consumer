#### Building

    docker-compose build

#### Running

    docker-compose run --rm consumer

#### Testing

    docker-compose run --rm test
    
#### Check Linting / Static Analysis

    docker-compose run --rm lint    
    docker-compose run --rm phpstan
    
#### Check the Localstack SQS Queue has been created
    
    docker-compose up localstack
    docker-compose exec localstack awslocal sqs list-queues
