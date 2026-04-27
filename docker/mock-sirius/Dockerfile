FROM stoplight/prism:5.15.9@sha256:b5d38cea6fc64970e4756dd30611770587cc84d6f4f2a8f83bec72452b226ded

RUN apk --no-cache add curl

COPY docker/mock-sirius/openapi.yml /app/mock-sirius/openapi.yml

EXPOSE 4011

HEALTHCHECK CMD curl --fail http://0.0.0.0:4011/health-check || exit 1
