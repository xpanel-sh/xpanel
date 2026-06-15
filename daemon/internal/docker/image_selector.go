package docker

import (
	"fmt"
)

// SelectImage determina la imagen Docker y el comando a ejecutar
// basado en el stack tecnológico solicitado.
func SelectImage(projectType, webServer, phpVersion string) (string, []string) {
	var imageName string
	var cmd []string

	switch projectType {
	case "php":
		if webServer == "nginx" {
			// Usamos una imagen que tenga FPM. En un escenario real,
			// necesitaríamos un contenedor sidecar de Nginx o una imagen combinada.
			// Para simplificar este MVP, usaremos una imagen all-in-one popular o standard.
			// Ej: serversideup/php:8.2-fpm-nginx es excelente para esto.
			imageName = fmt.Sprintf("serversideup/php:%s-fpm-nginx", phpVersion)
		} else {
			// Apache standard official image
			imageName = fmt.Sprintf("php:%s-apache", phpVersion)
			cmd = []string{"sh", "-lc", "printf 'DirectoryIndex default.php index.php index.html\\n' > /etc/apache2/conf-available/xpanel-directory-index.conf && a2enconf xpanel-directory-index >/dev/null && apache2-foreground"}
		}
	case "node":
		imageName = "node:18-alpine"
		cmd = []string{"node", "index.js"}
	case "python":
		imageName = "python:3.10-alpine"
		cmd = []string{"python", "app.py"}
	case "static":
		fallthrough
	default:
		imageName = "nginx:alpine"
	}

	return imageName, cmd
}
