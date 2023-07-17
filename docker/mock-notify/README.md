#### Running

    docker run --init --rm -it -v $(pwd)/docker/mock-notify:/app/mock-notify -p "4010:4010" stoplight/prism:3 mock -h 0.0.0.0 "/app/mock-notify/openapi.yml"

or

    docker compose run --service-ports mock-notify mock -h 0.0.0.0 /app/mock-notify/openapi.yml

#### Making requests

Append &__example=..., &__code=500, etc to the url


    http://0.0.0.0:4010/v2/notifications?reference=dignissimos?reference=no_notifications&__example=none

    http://0.0.0.0:4010/v2/notifications?reference=dignissimos?reference=no_notifications&__example=one

## References

- https://meta.stoplight.io/docs/prism/docs/getting-started/03-cli.md
- https://meta.stoplight.io/docs/prism/docs/guides/multiple-documents.md
- https://github.com/stoplightio/prism/blob/master/examples/petstore.oas3.yaml
- https://11sigma.com/blog/2019-10-11--prism-tutorial
- https://medium.com/@m_arlandy/contract-testing-for-microservices-using-swagger-prism-and-dredd-efdd463b9433
- https://github.com/apiaryio/dredd
- https://medium.com/@marc.calder/creating-meaningful-api-mocks-without-re-writing-the-whole-api-bb4054525214
- https://github.com/stoplightio/prism/issues/111
