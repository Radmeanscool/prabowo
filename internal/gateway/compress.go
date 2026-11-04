package gateway

import (
	"compress/zlib"
	"context"
	"encoding/json"
	"errors"
	"io"
)

// decodeZlib membaca satu zlib-stream dari pr (diisi chunk binary oleh
// readLoop) lalu mengirim setiap payload JSON ke packets.
func (c *Client) decodeZlib(ctx context.Context, pr io.Reader, packets chan<- Payload, errs chan<- error) {
	zr, err := zlib.NewReader(pr)
	if err != nil {
		sendErr(errs, err)
		return
	}
	defer func() { _ = zr.Close() }()

	dec := json.NewDecoder(zr)
	for {
		var p Payload
		if err := dec.Decode(&p); err != nil {
			if errors.Is(err, io.EOF) || errors.Is(err, io.ErrClosedPipe) {
				sendErr(errs, errClosed)
				return
			}
			sendErr(errs, err)
			return
		}
		select {
		case packets <- p:
		case <-ctx.Done():
			return
		}
	}
}

func sendErr(errs chan<- error, err error) {
	select {
	case errs <- err:
	default:
	}
}
