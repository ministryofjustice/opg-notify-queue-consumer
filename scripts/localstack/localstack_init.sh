awslocal sqs create-queue --queue-name notify --attributes VisibilityTimeout=30,ReceiveMessageWaitTimeSeconds=0
awslocal --endpoint-url=http://localhost:4572 s3 mb s3://localbucket
