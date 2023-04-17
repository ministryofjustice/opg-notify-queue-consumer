FROM stoplight/prism:4

RUN apk --no-cache add curl

COPY docker/mock-notify/openapi.yml /app/mock-notify/openapi.yml

HEALTHCHECK CMD curl --fail http://0.0.0.0:4010/health-check || exit 1
