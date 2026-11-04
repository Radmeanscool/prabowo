package rest

import (
	"context"
	"net/http"
)

// Me mengambil akun pemilik token.
func (c *Client) Me(ctx context.Context) (*User, error) {
	var user User
	if err := c.do(ctx, http.MethodGet, "/users/@me", nil, &user); err != nil {
		return nil, err
	}
	return &user, nil
}
