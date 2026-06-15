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
			imageName = fmt.Sprintf("serversideup/php:%s-fpm-nginx", phpVersion)
		} else {
			// Custom XPanel image: php:*-apache + pdo_mysql + mysqli pre-installed.
			// Built by install.sh / update.sh via docker/php/Dockerfile.
			imageName = fmt.Sprintf("xpanel-php:%s-apache", phpVersion)
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
