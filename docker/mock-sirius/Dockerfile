FROM stoplight/prism:5.14.3@sha256:fab47aee53c3007facd5d23e7957b1fc312b30dddad27bf570c2cb4a51c5f959

RUN apk --no-cache add curl

COPY docker/mock-sirius/openapi.yml /app/mock-sirius/openapi.yml

EXPOSE 4011

HEALTHCHECK CMD curl --fail http://0.0.0.0:4011/health-check || exit 1
