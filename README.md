## Local setup

Add the following alias to your shell - this will allow you to use a 
[jakzal/phpqa](https://hub.docker.com/r/jakzal/phpqa/) container for running common php tools

    alias phpqa='docker run --init -it --rm -v "$(pwd):/project" -v "$(pwd)/tmp-phpqa:/tmp" -w /project jakzal/phpqa:php7.4-alpine'
    
*macOS note*: If you find commands like composer too slow due to the known issues with file mounts, then try using your 
local native equivalent 

### Building

    cp local.env.example local.env
    
    # Install dependencies on your host machine
    phpqa composer install --prefer-dist --no-interaction --no-scripts
    
    # Update the local.env file with any secret credentials when testing external services
    docker-compose build consumer

### Running

     docker-compose up localstack
     docker-compose up consumer

## Testing

    docker-compose run --rm test
    
## Check Linting / Static Analysis

    docker-compose run --rm lint    
    docker-compose run --rm phpstan
    
#### Check the Localstack SQS Queue has been created
    
    docker-compose up localstack
    docker-compose exec localstack awslocal sqs list-queues

Create an S3 bucket

    docker-compose exec localstack awslocal --endpoint-url=http://localhost:4572 s3 mb s3://localbucket

Check it exists

    docker-compose exec localstack awslocal --endpoint-url=http://localhost:4572 s3 ls
    
Add a file

    docker-compose exec localstack awslocal --endpoint-url=http://localhost:4572 s3 cp /tmp/fixtures/sample_doc.pdf s3://localbucket  
    
List all files
      
    docker-compose exec localstack awslocal --endpoint-url=http://localhost:4572 s3 ls s3://localbucket    

List Queues

    docker-compose exec localstack awslocal --endpoint-url=http://localhost:4576 sqs list-queues
    
Add a message to queue

    docker-compose exec localstack awslocal --endpoint-url=http://localhost:4576 sqs send-message --queue-url http://localstack:4576/queue/notify --message-body '{"uuid":"asd-123","filename":"this_is_a_test.pdf","documentId":"1234"}'
    
Receive a message
    
    docker-compose exec localstack awslocal --endpoint-url=http://localhost:4576 sqs receive-message --queue-url http://localhost:4576/queue/notify
    
Delete a message
    
    docker-compose exec localstack awslocal --endpoint-url=http://localhost:4576 sqs delete-message --queue-url http://localhost:4576/queue/notify --receipt-handle <HANDLE>


## References

- https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/welcome.html
- https://docs.notifications.service.gov.uk/php.html#send-a-precompiled-letter
- http://docs.guzzlephp.org/en/stable/
