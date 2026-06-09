_xpanel_completions() {
  local cur prev
  COMPREPLY=()
  cur="${COMP_WORDS[COMP_CWORD]}"
  prev="${COMP_WORDS[COMP_CWORD-1]}"

  local commands="update actualizar doctor diagnostico uninstall eliminar reinstall reinstalar idioma language lang config status estado logs log backup respaldo site sitio ssl i18n-audit i18n-lint version access acceso port puerto help ayuda"

  if [[ $COMP_CWORD -eq 1 ]]; then
    COMPREPLY=( $(compgen -W "$commands" -- "$cur") )
    return 0
  fi

  case "${COMP_WORDS[1]}" in
    site|sitio)
      COMPREPLY=( $(compgen -W "list create delete remove eliminar restart reiniciar --json" -- "$cur") )
      ;;
    ssl)
      COMPREPLY=( $(compgen -W "status check verificar setup renew" -- "$cur") )
      ;;
    backup|respaldo)
      COMPREPLY=( $(compgen -W "create list restore prune" -- "$cur") )
      ;;
    config)
      if [[ $COMP_CWORD -eq 2 ]]; then
        COMPREPLY=( $(compgen -W "get set list" -- "$cur") )
      elif [[ $COMP_CWORD -eq 3 ]]; then
        COMPREPLY=( $(compgen -W "domain port lang language idioma" -- "$cur") )
      fi
      ;;
    update|actualizar)
      COMPREPLY=( $(compgen -W "check verificar --dry-run dry-run simular --rollback rollback revertir" -- "$cur") )
      ;;
    logs|log)
      COMPREPLY=( $(compgen -W "panel db redis proxy daemon -f --follow --since --lines" -- "$cur") )
      ;;
    status|estado)
      COMPREPLY=( $(compgen -W "--json" -- "$cur") )
      ;;
  esac
}

complete -F _xpanel_completions xpanel
