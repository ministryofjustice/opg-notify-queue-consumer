## Useful localstack commands

Create an S3 bucket

    docker compose --project-name notify-queue-consumer exec localstack awslocal --endpoint-url=http://localstack:4566 s3 mb s3://localbucket

Check it exists

    docker compose --project-name notify-queue-consumer exec localstack awslocal --endpoint-url=http://localstack:4566 s3 ls

Add a file

    docker compose --project-name notify-queue-consumer exec localstack awslocal --endpoint-url=http://localstack:4566 s3 cp /tmp/fixtures/sample_doc.pdf s3://localbucket

List all files

    docker compose --project-name notify-queue-consumer exec localstack awslocal --endpoint-url=http://localstack:4566 s3 ls s3://localbucket

List Queues

    docker compose --project-name notify-queue-consumer exec localstack awslocal --endpoint-url=http://localstack:4566 sqs list-queues

Add a message to queue

    docker compose --project-name notify-queue-consumer exec localstack awslocal --endpoint-url=http://localstack:4566 sqs send-message --queue-url http://localstack:4566/000000000000/notify --message-body '{"uuid":"asd-123","filename":"this_is_a_test.pdf","documentId":"1234"}'

Receive a message

    docker compose --project-name notify-queue-consumer exec localstack awslocal --endpoint-url=http://localstack:4566 sqs receive-message --queue-url http://localstack:4566/000000000000/notify

Delete a message

    docker compose --project-name notify-queue-consumer exec localstack awslocal --endpoint-url=http://localstack:4566 sqs delete-message --queue-url http://localstack:4566/000000000000/notify --receipt-handle <HANDLE>
