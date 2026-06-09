package main

import (
	"log"
	"xpanel/internal/api"
)

func main() {
	log.Println("🚀 XPanel Daemon starting on 127.0.0.1:9090")
	api.Start()
}
