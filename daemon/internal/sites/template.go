package sites

import (
	"fmt"
	"html"
	"os"
	"path/filepath"
	"strings"
	"time"

	xenv "xpanel/internal/env"
	model "xpanel/internal/types"
)

type PreparedSite struct {
	HostDir    string
	TargetDir  string
	WorkingDir string
}

func Prepare(req model.CreateSiteRequest) (PreparedSite, error) {
	domain := strings.TrimSuffix(strings.ToLower(strings.TrimSpace(req.Domain)), ".")
	if domain == "" {
		return PreparedSite{}, fmt.Errorf("domain is required")
	}

	hostDir := filepath.Join(xenv.BasePath(), "runtime", "sites", domain)
	if err := os.MkdirAll(hostDir, 0755); err != nil {
		return PreparedSite{}, err
	}

	page := landingPage(domain, req.Type, req.WebServer, req.PhpVersion)
	files := map[string]string{
		"index.html":        page,
		"public/index.html": page,
	}

	switch req.Type {
	case "node":
		files["index.js"] = nodeEntrypoint()
	case "python":
		files["app.py"] = pythonEntrypoint()
	case "php":
		files["index.php"] = phpEntrypoint()
		files["public/index.php"] = phpEntrypoint()
	}

	for name, content := range files {
		path := filepath.Join(hostDir, filepath.FromSlash(name))
		if _, err := os.Stat(path); err == nil {
			continue
		}
		if err := os.MkdirAll(filepath.Dir(path), 0755); err != nil {
			return PreparedSite{}, err
		}
		if err := os.WriteFile(path, []byte(content), 0644); err != nil {
			return PreparedSite{}, err
		}
	}

	targetDir := "/var/www/html"
	workingDir := ""
	if req.Type == "static" {
		targetDir = "/usr/share/nginx/html"
	}
	if req.Type == "node" || req.Type == "python" {
		targetDir = "/app"
		workingDir = "/app"
	}

	return PreparedSite{
		HostDir:    hostDir,
		TargetDir:  targetDir,
		WorkingDir: workingDir,
	}, nil
}

func landingPage(domain string, projectType string, webServer string, phpVersion string) string {
	escapedDomain := html.EscapeString(domain)
	stack := html.EscapeString(stackLabel(projectType, webServer, phpVersion))
	createdAt := html.EscapeString(time.Now().UTC().Format("2006-01-02 15:04 UTC"))

	return fmt.Sprintf(`<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>%s está listo en XPanel</title>
  <style>
    :root { color-scheme: dark; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
    body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: radial-gradient(circle at top left, #233876, transparent 34rem), linear-gradient(135deg, #050816, #101827 55%%, #020617); color: #f8fafc; }
    main { width: min(92vw, 760px); padding: 3rem; border: 1px solid rgba(148, 163, 184, .28); border-radius: 28px; background: rgba(15, 23, 42, .76); box-shadow: 0 30px 90px rgba(0, 0, 0, .38); backdrop-filter: blur(18px); }
    .badge { display: inline-flex; gap: .5rem; align-items: center; padding: .45rem .75rem; border-radius: 999px; background: rgba(34, 197, 94, .13); color: #86efac; font-size: .85rem; font-weight: 700; }
    h1 { margin: 1.25rem 0 .75rem; font-size: clamp(2.4rem, 7vw, 4.8rem); line-height: .95; letter-spacing: -.06em; }
    p { margin: 0; color: #cbd5e1; font-size: 1.05rem; line-height: 1.7; }
    code { color: #fde68a; background: rgba(250, 204, 21, .1); padding: .15rem .35rem; border-radius: .45rem; }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-top: 2rem; }
    .card { padding: 1rem; border-radius: 18px; background: rgba(255, 255, 255, .06); border: 1px solid rgba(255, 255, 255, .08); }
    .label { display: block; color: #94a3b8; font-size: .78rem; text-transform: uppercase; letter-spacing: .12em; margin-bottom: .35rem; }
    .value { color: #fff; font-weight: 800; word-break: break-word; }
    footer { margin-top: 2rem; color: #94a3b8; font-size: .9rem; }
  </style>
</head>
<body>
  <main>
    <span class="badge">✓ Sitio creado correctamente</span>
    <h1>%s está en línea.</h1>
    <p>Esta es la página inicial generada por <strong>XPanel</strong>. Reemplaza este archivo con el código de tu proyecto cuando estés listo para publicar.</p>
    <div class="grid">
      <div class="card"><span class="label">Dominio</span><span class="value">%s</span></div>
      <div class="card"><span class="label">Stack</span><span class="value">%s</span></div>
      <div class="card"><span class="label">Creado</span><span class="value">%s</span></div>
    </div>
    <footer>Archivo inicial: <code>index.html</code>. Puedes reemplazarlo por tu app, CMS o landing page.</footer>
  </main>
</body>
</html>
`, escapedDomain, escapedDomain, escapedDomain, stack, createdAt)
}

func stackLabel(projectType string, webServer string, phpVersion string) string {
	switch projectType {
	case "php":
		return fmt.Sprintf("PHP %s / %s", phpVersion, webServer)
	case "node":
		return "Node.js"
	case "python":
		return "Python"
	default:
		return "Static HTML"
	}
}

func phpEntrypoint() string {
	return `<?php readfile(__DIR__ . '/index.html');`
}

func nodeEntrypoint() string {
	return `const http = require("http");
const fs = require("fs");
const path = require("path");

const port = process.env.PORT || 80;
const html = fs.readFileSync(path.join(__dirname, "index.html"));

http.createServer((request, response) => {
  response.writeHead(200, { "Content-Type": "text/html; charset=utf-8" });
  response.end(html);
}).listen(port, "0.0.0.0");
`
}

func pythonEntrypoint() string {
	return `from http.server import SimpleHTTPRequestHandler, ThreadingHTTPServer

server = ThreadingHTTPServer(("0.0.0.0", 80), SimpleHTTPRequestHandler)
server.serve_forever()
`
}
