BINARY := bin/discordgo-selfbot
PKG := ./cmd/bot

.PHONY: build run test race vet fmt clean

build:
	go build -trimpath -ldflags "-s -w" -o $(BINARY) $(PKG)

run:
	go run $(PKG) -gateway

test:
	go test ./...

race:
	go test -race ./...

vet:
	go vet ./...

fmt:
	@test -z "$$(gofmt -l .)" || (gofmt -l . && exit 1)

clean:
	rm -rf bin
