FROM stoplight/prism:5.15.10@sha256:586d1f0f94f8d0eaf20b26b8b41f985f2a2d494bea297bd3988c3de3eb87094e

RUN apk --no-cache add curl

COPY docker/mock-sirius/openapi.yml /app/mock-sirius/openapi.yml

EXPOSE 4011

HEALTHCHECK CMD curl --fail http://0.0.0.0:4011/health-check || exit 1
