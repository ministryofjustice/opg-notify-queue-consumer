# Adapted from: https://github.com/jrr/localstack-example/blob/master/wait-for-localstack-ready.sh
# See https://spin.atomicobject.com/2020/02/03/localstack-terraform-circleci/
# Wait for prism log message that denotes it's available as container can't be queried from circleci host
CONTAINER=$1

echo "Waiting for Prism in container $CONTAINER"

# shellcheck disable=SC2034
for i in {1..30}; do
  LOGS=$(docker logs "$CONTAINER" --since 5m)
  if echo "$LOGS" | grep 'Prism is listening'; then
    echo "Prism is ready!"
    break;
  fi
  echo "Waiting..."
  sleep 1
done
