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
    
Add a message to queue

    docker-compose exec localstack awslocal --endpoint-url=http://localhost:4576 sqs send-message --queue-url http://localstack:4576/queue/notify --message-body '{"uuid":"asd-123","filename":"this_is_a_test.pdf","documentId":"1234"}'
    
Receive a message
    
    docker-compose exec localstack awslocal --endpoint-url=http://localhost:4576 sqs receive-message --queue-url http://localhost:4576/queue/notify

Create an S3 bucket

    docker-compose exec localstack awslocal --endpoint-url=http://localhost:4572 s3 mb s3://localbucket

Check it exists

    docker-compose exec localstack awslocal --endpoint-url=http://localhost:4572 s3 ls
    
Add a file

    docker-compose exec localstack awslocal --endpoint-url=http://localhost:4572 s3 cp /tmp/fixtures/sample_doc.pdf s3://localbucket  
    
List all files
      
    docker-compose exec localstack awslocal --endpoint-url=http://localhost:4572 s3 ls s3://localbucket    
