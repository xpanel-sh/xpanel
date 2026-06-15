package sites

import (
	"fmt"
	"os"
	"path/filepath"
	"strings"

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

	isEmpty, err := isDirEmpty(hostDir)
	if err != nil {
		return PreparedSite{}, err
	}
	if isEmpty {
		for name, content := range starterFiles(req, domain) {
			path := filepath.Join(hostDir, filepath.FromSlash(name))
			if err := os.MkdirAll(filepath.Dir(path), 0755); err != nil {
				return PreparedSite{}, err
			}
			if err := os.WriteFile(path, []byte(content), 0644); err != nil {
				return PreparedSite{}, err
			}
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

func isDirEmpty(path string) (bool, error) {
	entries, err := os.ReadDir(path)
	if err != nil {
		return false, err
	}

	return len(entries) == 0, nil
}

func starterFiles(req model.CreateSiteRequest, domain string) map[string]string {
	page := defaultPage()

	switch req.Type {
	case "node":
		return map[string]string{
			"index.html": page,
			"index.js":   nodeEntrypoint(),
		}
	case "python":
		return map[string]string{
			"index.html": page,
			"app.py":     pythonEntrypoint(),
		}
	case "php":
		return map[string]string{
			".htaccess":          directoryIndexRule(),
			"default.php":        page,
			"index.php":          phpEntrypoint(),
			"public/.htaccess":   directoryIndexRule(),
			"public/default.php": page,
			"public/index.php":   phpEntrypoint(),
		}
	case "static":
		fallthrough
	default:
		return map[string]string{
			"index.html": page,
		}
	}
}

func defaultPage() string {
	return `<!DOCTYPE html>
<html lang="es">
<head>
  <title>Pagina por defecto</title>
  <link rel="icon" type="image/x-icon" href="https://xpanel.sh/assets/xpanel.png">
  <meta charset="utf-8">
  <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
  <meta content="Pagina por defecto" name="description">
  <meta content="width=device-width, initial-scale=1" name="viewport">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-color: #ffffff;
      --text-color: #111827;
      --text-muted: #6b7280;
      --nav-bg: oklch(96.7% 0.001 286.375);
      --color-icono: #000;
    }

    @media (prefers-color-scheme: dark) {
      :root {
        --bg-color: #747483;
        --text-color: #f8fafc;
        --text-muted: #94a3b8;
        --nav-bg: #525364;
        --color-icono: #ffffff;
      }
    }

    body {
      margin: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      width: 100vw;
      height: 100vh;
      min-height: 675px;
      background-color: var(--bg-color);
      color: var(--text-color);
    }

    p {
      width: 100%;
      left: 0;
      font-size: 16px;
      font-family: 'DM Sans', sans-serif;
      font-weight: 400;
      letter-spacing: 0;
      text-align: center;
      vertical-align: top;
      max-width: 550px;
      color: var(--text-color);
      margin: 0;
    }

    a:hover {
      cursor: pointer;
      color: #673DE6;
      text-decoration: underline;
    }

    h1 {
      font-family: 'DM Sans', sans-serif;
      font-size: 24px;
      font-weight: 700;
      letter-spacing: 0;
      text-align: center;
      margin: 8px;
    }

    img {
      margin-bottom: 30px;
    }

    .content {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      width: 100%;
      height: 100%;
    }

    .ic-launch {
      margin-left: 10.5px;
      width: 21px !important;
      height: 20px !important;
    }

    .link-container {
      margin-top: 32px;
      margin-bottom: 32px;
    }

    .link {
      display: flex;
      flex-direction: row;
      align-items: center;
      justify-content: center;
      font-family: 'DM Sans', sans-serif;
      font-style: normal;
      font-weight: 700;
      font-size: 14px;
      color: var(--color-icono);
      margin-top: 8px;
      text-decoration: none;
    }

    .main-image {
      width: 100%;
      max-width: 650px;
      max-height: 406px;
      height: auto;
    }

    .navigation {
      width: 100%;
      height: 72px;
      display: flex;
      margin: 0;
      padding: 0;
      flex-direction: row;
      align-items: center;
      justify-content: center;
      background-color: var(--nav-bg);
    }

    @media screen and (max-width: 580px) and (min-width: 0) {
      h1,
      p,
      .link-container {
        width: 80%;
      }
    }

    @media screen and (min-width: 650px) and (min-height: 0) and (max-height: 750px) {
      .link-container {
        margin-top: 12px;
      }

      h1 {
        margin-top: 0;
        margin-bottom: 0;
      }
    }

    .footer {
      width: 100%;
      padding: 20px;
      text-align: center;
      font-family: 'DM Sans', sans-serif;
      font-size: 13px;
      color: var(--text-color);
      box-sizing: border-box;
    }
  </style>
</head>

<body>
  <nav class="navigation">
    <a href="https://xpanel.sh" rel="nofollow" target="_blank">
      <svg version="1.0" xmlns="http://www.w3.org/2000/svg" width="150" height="30" viewBox="0 0 565.000000 597.000000" preserveAspectRatio="xMidYMid meet">
        <g transform="translate(0.000000,597.000000) scale(0.100000,-0.100000)" fill="var(--color-icono)" stroke="none">
          <path d="M670 5890 c0 -3 20 -39 43 -80 45 -78 75 -130 482 -835 134 -231 318 -550 410 -710 92 -159 292 -506 445 -770 152 -264 363 -628 467 -810 105 -181 241 -418 303 -525 63 -107 210 -364 328 -570 118 -206 235 -410 259 -452 39 -67 49 -78 76 -82 18 -3 183 -8 367 -11 311 -5 581 3 596 17 9 10 -29 82 -100 193 -36 55 -87 138 -114 185 -27 47 -87 150 -134 230 -105 181 -192 328 -233 400 -39 67 -139 240 -170 295 -13 22 -43 74 -68 115 -81 135 -327 559 -327 565 0 3 515 6 1145 7 l1146 3 -84 145 c-353 613 -922 1598 -932 1612 -10 15 -18 5 -61 -70 -28 -48 -129 -222 -224 -387 -95 -165 -192 -333 -215 -373 l-41 -72 -618 -1 c-340 0 -619 1 -620 3 -3 4 -158 274 -206 358 -20 36 -77 135 -127 220 -178 307 -252 435 -263 461 -11 24 -4 40 85 195 169 294 315 546 372 642 30 51 52 96 48 100 -8 8 -2035 10 -2035 2z" />
          <path d="M2970 5889 c0 -3 42 -78 93 -166 50 -88 160 -278 242 -421 l150 -261 493 -1 c270 0 492 2 492 5 0 5 -88 158 -376 653 l-112 192 -491 3 c-270 1 -491 -1 -491 -4z" />
          <path d="M533 5808 c-6 -7 -61 -101 -123 -208 -62 -107 -168 -292 -237 -412 l-124 -216 82 -144 c45 -78 139 -242 209 -363 213 -368 833 -1443 900 -1560 27 -47 385 -667 463 -800 38 -66 164 -284 280 -485 115 -201 264 -459 332 -575 67 -116 146 -253 175 -305 30 -52 76 -134 104 -181 28 -47 94 -160 146 -252 52 -91 97 -166 100 -167 3 0 41 62 84 138 269 465 406 705 406 713 0 4 -39 75 -86 156 -47 81 -112 193 -144 248 -87 152 -221 382 -387 670 -83 143 -193 334 -245 425 -52 91 -120 208 -150 260 -30 52 -99 172 -153 265 -54 94 -147 253 -205 355 -59 102 -151 262 -205 355 -54 94 -147 253 -205 355 -101 175 -233 405 -540 935 -73 127 -142 246 -153 265 -10 19 -60 105 -109 190 -50 85 -111 191 -136 235 -67 117 -67 118 -79 103z" />
          <path d="M2771 5707 c-36 -61 -113 -195 -172 -297 -58 -102 -116 -201 -127 -220 -10 -19 -43 -75 -71 -124 -61 -105 -68 -72 62 -298 190 -330 349 -604 364 -626 14 -23 19 -17 155 220 77 134 187 323 244 421 57 98 104 183 104 187 0 7 -144 260 -469 822 -9 16 -18 28 -21 28 -3 0 -34 -51 -69 -113z" />
          <path d="M3399 4803 c-31 -54 -140 -244 -243 -421 -102 -178 -186 -326 -186 -328 0 -2 222 -3 492 -2 l493 3 217 375 c119 206 229 396 243 423 l26 47 -493 0 -493 0 -56 -97z" />
          <path d="M3540 2906 c0 -2 19 -37 43 -77 63 -106 135 -232 292 -504 76 -132 144 -247 151 -255 12 -12 94 -15 521 -18 280 -2 519 -1 533 3 18 5 38 30 74 93 27 48 129 225 227 394 98 168 185 320 194 337 l16 31 -1026 0 c-564 0 -1025 -2 -1025 -4z" />
          <path d="M1072 2763 c-66 -115 -314 -545 -487 -843 -160 -279 -251 -436 -342 -592 -42 -73 -103 -179 -135 -236 l-60 -103 108 -187 c302 -526 367 -638 378 -649 9 -10 37 31 126 185 211 366 476 825 635 1102 86 151 191 331 231 399 41 68 74 133 74 144 0 11 -45 98 -99 191 -54 94 -140 243 -191 331 -152 263 -183 316 -191 321 -4 2 -25 -26 -47 -63z" />
          <path d="M1658 1793 c-13 -27 -76 -138 -139 -248 -239 -412 -725 -1254 -785 -1359 -35 -61 -61 -113 -59 -117 3 -3 224 -5 492 -2 l488 4 56 97 c31 53 90 155 131 227 146 252 232 401 285 492 29 51 53 96 53 101 0 4 -72 133 -161 287 -88 154 -198 344 -242 422 -45 79 -85 143 -88 143 -4 0 -17 -21 -31 -47z" />
          <path d="M3437 883 c-13 -21 -61 -103 -107 -183 -46 -80 -146 -253 -222 -384 -76 -131 -136 -241 -134 -243 3 -3 224 -4 492 -4 l487 1 60 102 c60 104 224 388 356 617 39 68 71 125 71 127 0 2 -220 4 -490 4 l-490 0 -23 -37z" />
        </g>
      </svg>
    </a>
  </nav>
  <div class="content">
    <img class="main-image" src="https://lukaszadam.com/images/free-illustrations/free-svg-illustration-hosting.svg" alt="Hosting listo">
    <h1>Ya todo esta listo!</h1>
    <p>Todo lo que tienes que hacer ahora es cargar los archivos de tu sitio web y empezar tu aventura. Mira como hacerlo aqui:</p>
    <div class="link-container">
      <a class="link" href="https://support.xpanel.sh/en/articles/4455931-how-can-i-migrate-website-to-xpanel" rel="nofollow" target="_blank">
        Como puedo migrar un sitio web a xPanel?
        <svg class="ic-launch" viewBox="0 0 21 20" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path fill-rule="evenodd" clip-rule="evenodd" d="M16.3333 15.8333H4.66667V4.16667H10.5V2.5H4.66667C3.74167 2.5 3 3.25 3 4.16667V15.8333C3 16.75 3.74167 17.5 4.66667 17.5H16.3333C17.25 17.5 18 16.75 18 15.8333V10H16.3333V15.8333ZM12.1667 2.5V4.16667H15.1583L6.96667 12.3583L8.14167 13.5333L16.3333 5.34167V8.33333H18V2.5H12.1667Z" fill="var(--color-icono)" />
        </svg>
      </a>
      <a class="link" href="https://support.xpanel.sh/en/articles/3220304-how-to-install-wordpress-using-auto-installer" rel="nofollow" target="_blank">
        Como instalar WordPress usando el instalador automatico?
        <svg class="ic-launch" viewBox="0 0 21 20" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path fill-rule="evenodd" clip-rule="evenodd" d="M16.3333 15.8333H4.66667V4.16667H10.5V2.5H4.66667C3.74167 2.5 3 3.25 3 4.16667V15.8333C3 16.75 3.74167 17.5 4.66667 17.5H16.3333C17.25 17.5 18 16.75 18 15.8333V10H16.3333V15.8333ZM12.1667 2.5V4.16667H15.1583L6.96667 12.3583L8.14167 13.5333L16.3333 5.34167V8.33333H18V2.5H12.1667Z" fill="var(--color-icono)" />
        </svg>
      </a>
    </div>
  </div>
  <footer class="footer">&copy; 2026 xPanel. All rights reserved.</footer>
</body>
</html>
`
}

func phpEntrypoint() string {
	return `<?php
$default = __DIR__ . '/default.php';
$html = __DIR__ . '/index.html';

if (is_file($default)) {
    require $default;
    return;
}

if (is_file($html)) {
    readfile($html);
    return;
}

http_response_code(404);
echo 'No default.php, index.php or index.html file found.';
`
}

func directoryIndexRule() string {
	return "DirectoryIndex default.php index.php index.html\n"
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
