FROM stoplight/prism:5.14.3@sha256:fab47aee53c3007facd5d23e7957b1fc312b30dddad27bf570c2cb4a51c5f959

RUN apk --no-cache add curl

COPY docker/mock-notify/openapi.yml /app/mock-notify/openapi.yml

HEALTHCHECK CMD curl --fail http://0.0.0.0:4010/health-check || exit 1
