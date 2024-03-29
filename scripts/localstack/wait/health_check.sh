#!/usr/bin/env bash

queues=$(awslocal sqs list-queues)
echo $queues | grep '"http://sqs.us-east-1.localhost.localstack.cloud:4566/000000000000/notify"' || exit 1

buckets=$(awslocal s3 ls)
echo $buckets | grep "localbucket" || exit 1
