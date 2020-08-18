# Adapted from: https://github.com/jrr/localstack-example/blob/master/wait-for-localstack-ready.sh
# See https://spin.atomicobject.com/2020/02/03/localstack-terraform-circleci/
CONTAINER=$(docker ps -q)

echo "waiting for Localstack to report 'Ready.' from container $CONTAINER"

# shellcheck disable=SC2034
for i in {1..15}; do
  LOGS=$(docker logs "$CONTAINER" --since 5m)
  if echo "$LOGS" | grep 'Ready.'; then
    echo "Localstack is ready!"
    break;
  fi
  echo "waiting.."
  sleep 2
done
