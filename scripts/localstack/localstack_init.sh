awslocal sqs create-queue --queue-name notify --attributes VisibilityTimeout=30,ReceiveMessageWaitTimeSeconds=0
awslocal --endpoint-url=http://localhost:4572 s3 mb s3://localbucket

# Add a sample file to s3 bucket
awslocal --endpoint-url=http://localhost:4572 s3 cp /tmp/fixtures/sample_doc.pdf s3://localbucket

# Add a message pointing to sample file
awslocal --endpoint-url=http://localhost:4576 sqs send-message --queue-url http://localstack:4576/queue/notify --message-body '{"uuid":"asd-456","filename":"sample_doc.pdf","documentId":"1234"}'
