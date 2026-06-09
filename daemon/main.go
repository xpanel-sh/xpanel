package main

import (
	"context"
	"log"
	"net/http"
	"os"
	"os/signal"
	"syscall"
	"time"

	"xpanel/internal/api"
	"xpanel/internal/config"
)

func main() {
	// 1. Cargar configuración
	cfg := config.Load()

	// 2. Configurar Logger
	log.SetFlags(log.LstdFlags | log.Lshortfile)
	log.Println("🚀 XPanel Daemon iniciando...")

	// 3. Configurar Router
	router := api.NewRouter()

	// 4. Configurar Servidor
	srv := &http.Server{
		Addr:    ":" + cfg.Port,
		Handler: router,
	}

	// 5. Iniciar Servidor en Goroutine
	go func() {
		log.Printf("📡 Escuchando en puerto %s", cfg.Port)
		if err := srv.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			log.Fatalf("❌ Error iniciando servidor: %v", err)
		}
	}()

	// 6. Graceful Shutdown
	quit := make(chan os.Signal, 1)
	signal.Notify(quit, syscall.SIGINT, syscall.SIGTERM)
	<-quit
	log.Println("🛑 Deteniendo daemon...")

	ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()

	if err := srv.Shutdown(ctx); err != nil {
		log.Fatal("❌ Forzando cierre:", err)
	}

	log.Println("✅ Daemon detenido correctamente")
}
