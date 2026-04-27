FROM stoplight/prism:5.15.9@sha256:b5d38cea6fc64970e4756dd30611770587cc84d6f4f2a8f83bec72452b226ded

RUN apk --no-cache add curl

COPY docker/mock-notify/openapi.yml /app/mock-notify/openapi.yml

HEALTHCHECK CMD curl --fail http://0.0.0.0:4010/health-check || exit 1
