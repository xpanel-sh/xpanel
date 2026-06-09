package api

import (
	"os/exec"
	"net/http"
)

func dockerPS(w http.ResponseWriter, r *http.Request) {
	out, err := exec.Command("docker", "ps").CombinedOutput()
	if err != nil {
		http.Error(w, string(out), 500)
		return
	}
	w.Write(out)
}
