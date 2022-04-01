FROM golang:1.18.0-alpine

RUN apk update get && apk add --no-cache git

WORKDIR /src

RUN git clone https://github.com/kindacommander/grpc-health-check-server.git

RUN cd grpc-health-check-server/server && \
    go mod download && \
    go build -o /bin/serve