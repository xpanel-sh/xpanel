package api

import (
	"net/http"
)

func Start() {
	mux := http.NewServeMux()

	mux.HandleFunc("/health", health)
	mux.HandleFunc("/docker/ps", dockerPS)

	server := &http.Server{
		Addr:    "127.0.0.1:9090",
		Handler: mux,
	}

	server.ListenAndServe()
}
