FROM stoplight/prism:5.15.6@sha256:6640f7d73f18091fc9aaa73647de27ddb8b178615aabfc3ff30c5cb500f387a6

RUN apk --no-cache add curl

COPY docker/mock-sirius/openapi.yml /app/mock-sirius/openapi.yml

EXPOSE 4011

HEALTHCHECK CMD curl --fail http://0.0.0.0:4011/health-check || exit 1
