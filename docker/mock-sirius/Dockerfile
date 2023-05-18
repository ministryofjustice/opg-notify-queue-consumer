FROM stoplight/prism:5

RUN apk --no-cache add curl

COPY docker/mock-sirius/openapi.yml /app/mock-sirius/openapi.yml

EXPOSE 4011

HEALTHCHECK CMD curl --fail http://0.0.0.0:4011/health-check || exit 1
