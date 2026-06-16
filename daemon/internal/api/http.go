package api

import (
	"log"
	"net/http"
	"os"
)

func Start() {
	port := os.Getenv("XPANEL_DAEMON_PORT")
	if port == "" {
		port = "7070"
	}
	addr := "0.0.0.0:" + port

	log.Printf("XPanel Daemon listening on %s", addr)

	server := &http.Server{
		Addr:    addr,
		Handler: NewRouter(),
	}

	if err := server.ListenAndServe(); err != nil {
		log.Fatalf("daemon: %v", err)
	}
}
