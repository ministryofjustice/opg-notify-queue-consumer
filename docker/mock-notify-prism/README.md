#### Running

    docker run --init --rm -it -v $(pwd)/docker/mock-notify-prism:/app/mock-notify -p "4010:4010" stoplight/prism:3 mock -h 0.0.0.0 "/app/mock-notify/openapi.yml"
    
#### Making requests

Append &__example=..., &__code=500, etc to the url


    http://0.0.0.0:4010/v2/notifications?reference=dignissimos?reference=no_notifications&__example=none
    
    http://0.0.0.0:4010/v2/notifications?reference=dignissimos?reference=no_notifications&__example=one
    
## References

- https://github.com/stoplightio/prism/issues/111
- https://medium.com/@marc.calder/creating-meaningful-api-mocks-without-re-writing-the-whole-api-bb4054525214
