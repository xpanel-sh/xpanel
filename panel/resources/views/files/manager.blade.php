@php
    $isAdminFiles = ($scope ?? 'admin') === 'admin';
    $filesTitle = $isAdminFiles ? 'Archivos [Admin]' : 'Archivos';
    $filesBackRoute = $isAdminFiles ? route('admin.sites.index') : route('client.sites.index');
    $filesBaseUrl = $isAdminFiles ? url('/admin/files/api') : url('/client/files/api');
    $filesDomain = $domain ?? null;
    $filesSites = ($sites ?? collect())->map(fn ($site) => [
        'domain' => $site->domain,
        'tenant' => $site->tenant->name ?? null,
        'url' => $isAdminFiles ? route('admin.files.index', $site->domain) : route('client.files.index', $site->domain),
    ])->values();
@endphp

@push('styles')
    <link href="{{ asset('assets/files/editor.css') }}" rel="stylesheet" />
    <style>
        .xpanel-file-shell {
            height: 100%;
            min-height: 620px;
            grid-template-rows: 46px minmax(0, 1fr);
        }
        .xpanel-file-shell .ikode_editor_left {
            flex: 0 0 300px;
            min-width: 240px;
            width: auto;
            display: flex;
            flex-direction: column;
        }
        .xpanel-file-shell .ikode_editor_left .ikode_left_mode_panel {
            flex: 1 1 auto;
            min-height: 0;
        }
        .xpanel-file-shell .ikode_editor_right { flex: 0 0 310px; min-width: 260px; width: auto; }
        .xpanel-file-shell .ikode_editor_center { flex: 1 1 auto; min-width: 360px; }
        .xpanel-file-shell .ikode_editor_split { height: 100%; min-height: 0; }
        .xpanel-file-shell .ikode_editor_codepane { flex: 1 1 auto; min-height: 240px; }
        .xpanel-file-shell .ikode_editor_bottom { flex: 0 0 220px; min-height: 120px; }
        .xpanel-file-shell .ikode_editor_right.is-start-hidden { display: none; }
        .xpanel-file-shell .ikode_tabs_actions {
            height: 35px;
            padding: 4px 6px;
            gap: 5px;
            align-items: center;
            flex: 0 0 auto;
        }
        .xpanel-file-shell .ikode_tabs_action_btn {
            width: 26px;
            height: 26px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            line-height: 1;
            flex: 0 0 auto;
        }
        .xpanel-file-shell .ikode_tabs_action_btn:hover,
        .xpanel-file-shell .ikode_tabs_action_btn.is-active {
            background: rgba(59, 130, 246, .18);
        }
        .xpanel-file-shell.xpanel-editor-duplicated #xpanel_code_pane {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        }
        .xpanel-file-shell.xpanel-editor-duplicated #xpanel_monaco_editor {
            border-right: 1px solid hsl(var(--border));
        }
        .xpanel-file-shell .xpanel-monaco-clone {
            width: 100%;
            height: 100%;
            min-width: 0;
        }
        .xpanel-file-shell.xpanel-fullscreen {
            position: fixed;
            inset: 10px;
            z-index: 80;
            height: calc(100dvh - 20px);
            min-height: 0;
            border-radius: 10px;
        }
        .xpanel-file-shell .xpanel-site-select {
            width: min(240px, 28vw);
            height: 28px;
            font-size: 12px;
            padding-top: 0;
            padding-bottom: 0;
        }
        .xpanel-file-row {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 34px;
            padding: 7px 10px;
            border-radius: 8px;
            color: var(--muted-foreground);
            cursor: pointer;
            user-select: none;
        }
        .xpanel-file-row:hover,
        .xpanel-file-row.active {
            background: hsl(var(--muted));
            color: hsl(var(--foreground));
        }
        .xpanel-file-row .xpanel-file-name {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex: 1;
            font-size: 12px;
        }
        .xpanel-file-row .xpanel-file-size {
            font-size: 10px;
            color: var(--muted-foreground);
        }
        .xpanel-file-meta {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 9px 0;
            border-bottom: 1px solid hsl(var(--border));
            font-size: 12px;
        }
        .xpanel-file-drop {
            border: 1px dashed hsl(var(--border));
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            color: var(--muted-foreground);
            font-size: 12px;
        }
        .xpanel-file-drop.dragover {
            border-color: hsl(var(--primary));
            color: hsl(var(--primary));
            background: hsl(var(--primary) / 0.08);
        }
        #xpanel_file_list.dragover {
            outline: 1px dashed hsl(var(--primary));
            outline-offset: -6px;
            background: hsl(var(--primary) / 0.05);
        }
        .xpanel-file-empty {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 12px;
            color: var(--muted-foreground);
            text-align: center;
            background: hsl(var(--background));
            z-index: 5;
        }
        .xpanel-terminal-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 6px 10px;
            border-bottom: 1px solid hsl(var(--border));
            font-family: Consolas, 'Courier New', monospace;
            font-size: 12px;
        }
        .xpanel-terminal-selector {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-width: 0;
        }
        .xpanel-terminal-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 24px;
            padding: 0 8px;
            border-radius: 6px;
            background: hsl(var(--muted));
            color: hsl(var(--foreground));
        }
        .xpanel-terminal-actions {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .xpanel-terminal-action {
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid hsl(var(--border));
            border-radius: 6px;
        }
        .xpanel-terminal-action:hover { background: hsl(var(--muted)); }
        .xpanel-console-line {
            display: flex;
            gap: 10px;
            min-width: 0;
            padding: 2px 0;
        }
        .xpanel-console-time {
            color: var(--muted-foreground);
            flex: 0 0 auto;
        }
        .xpanel-console-text {
            min-width: 0;
            overflow-wrap: anywhere;
        }
        @media (max-width: 900px) {
            .xpanel-file-shell { height: 78dvh; min-height: 560px; }
            .xpanel-file-shell .xpanel-site-select { width: 150px; }
            .xpanel-file-shell .ikode_editor_header_center { padding-inline: 6px; }
        }
    </style>
@endpush


    <div class="ikode_editor_shell xpanel-file-shell bg-background border border-border" id="xpanel_file_shell">
        <div class="ikode_editor_header border-b border-border">
            <div class="ikode_editor_header_left">
                <div class="ikode_editor_actions">
                    <a href="{{ $filesBackRoute }}" class="ikode_header_btn" title="Volver">
                        <i class="ki-filled ki-left"></i>
                    </a>
                    <button class="ikode_header_btn ikode_header_btn_active" type="button" data-left-mode="explorer" title="Explorador">
                        <i class="ki-filled ki-folder"></i>
                    </button>
                    <button class="ikode_header_btn" type="button" data-left-mode="settings" title="Settings">
                        <i class="ki-filled ki-setting-2"></i>
                    </button>
                    <button class="ikode_header_btn" type="button" data-fm-action="refresh" title="Refrescar">
                        <i class="ki-filled ki-arrows-circle"></i>
                    </button>
                    <select id="xpanel_site_jump" class="kt-select xpanel-site-select" title="Raiz del gestor">
                        <option value="{{ $isAdminFiles ? route('admin.files.index') : route('client.files.index') }}">
                            {{ $isAdminFiles ? 'www/ todos los sitios' : 'www/ mis sitios' }}
                        </option>
                        @foreach($filesSites as $fileSite)
                            <option value="{{ $fileSite['url'] }}" @selected($filesDomain === $fileSite['domain'])>
                                {{ $fileSite['domain'] }}{{ $fileSite['tenant'] ? ' - ' . $fileSite['tenant'] : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="ikode_editor_header_center">
                <div class="flex items-center gap-3 min-w-0 w-full justify-center">
                    <div class="hidden md:flex items-center gap-2 min-w-0">
                        <i class="ki-filled ki-folder text-primary"></i>
                        <span class="text-sm font-semibold text-mono truncate">
                            {{ $filesTitle }} - <span class="font-mono text-primary">{{ $filesDomain ?: 'www/' }}</span>
                        </span>
                    </div>
                    <button class="kt-input max-w-96 ikode_quick_btn" type="button" id="xpanel_quick_focus">
                    <i class="ki-outline ki-magnifier"></i>
                    <input id="xpanel_file_filter" placeholder="Buscar archivo o carpeta" type="text">
                    </button>
                </div>
            </div>
            <div class="ikode_editor_header_right">
                <button class="ikode_header_btn ikode_header_btn_active" type="button" data-layout-toggle="left" title="Panel izquierdo">
                    <i class="ki-filled ki-row-horizontal"></i>
                </button>
                <button class="ikode_header_btn ikode_header_btn_active" type="button" data-layout-toggle="bottom" title="Consola inferior">
                    <i class="ki-filled ki-element-9"></i>
                </button>
                <button class="ikode_header_btn" type="button" data-layout-toggle="right" title="Panel de informacion">
                    <i class="ki-filled ki-grid-2"></i>
                </button>
                <button class="ikode_header_btn" type="button" data-layout-action="fullscreen" title="Pantalla completa">
                    <i class="ki-filled ki-maximize"></i>
                </button>
            </div>
        </div>

        <div class="ikode_editor_main">
            <aside class="ikode_editor_left border-r border-border" id="xpanel_left_pane">
                <div class="ikode_left_split ikode_left_mode_panel" id="xpanel_left_split" data-left-panel="explorer">
                    <div class="ikode_editor_lefthead border-b border-border">
                        <span>EXPLORADOR</span>
                        <div class="ikode_left_actions">
                            <button class="ikode_left_action_btn" type="button" data-fm-action="new-file" title="Nuevo archivo">
                                <i class="ki-filled ki-file-up"></i>
                            </button>
                            <button class="ikode_left_action_btn" type="button" data-fm-action="new-folder" title="Nueva carpeta">
                                <i class="ki-filled ki-folder-up"></i>
                            </button>
                        </div>
                    </div>

                    <div class="ikode_left_files" id="xpanel_left_files_pane">
                        <div id="xpanel_file_list"
                             ondragover="XPanelFM.dragOver(event)"
                             ondragleave="XPanelFM.dragLeave(event)"
                             ondrop="XPanelFM.drop(event)">
                            <div class="p-3 text-xs text-secondary-foreground">Cargando...</div>
                        </div>
                    </div>

                    <div class="ikode_left_bottom border-t border-border" id="xpanel_left_outline_pane">
                        <div class="ikode_left_bottom_tabs border-b border-border">
                            <button class="ikode_left_bottom_tab ikode_left_bottom_tab_active" type="button" data-outline-tab="outline">OUTLINE</button>
                            <button class="ikode_left_bottom_tab" type="button" data-outline-tab="timeline">TIMELINE</button>
                        </div>
                        <div class="ikode_left_bottom_body">
                            <div data-outline-view="outline">
                                <div class="ikode_left_item">
                                    <i class="ki-filled ki-folder"></i>
                                    <span class="min-w-0 truncate" id="xpanel_breadcrumb">/</span>
                                </div>
                                <div class="ikode_left_item">
                                    <i class="ki-filled ki-row-vertical"></i>
                                    <span id="xpanel_outline_count">0 elementos</span>
                                </div>
                                <div class="ikode_left_item">
                                    <i class="ki-filled ki-code"></i>
                                    <span id="xpanel_outline_file">Sin archivo abierto</span>
                                </div>
                            </div>
                            <div class="ikode_hidden" data-outline-view="timeline">
                                <div id="xpanel_timeline_list">
                                    <div class="ikode_left_item">
                                        <i class="ki-filled ki-time"></i>
                                        <span>Gestor listo.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ikode_left_mode_panel ikode_hidden" data-left-panel="settings" style="overflow: auto;">
                    <div class="ikode_simple_head border-b border-border">SETTINGS</div>
                    <div class="ikode_simple_list">
                        <div class="ikode_panel">
                            <div class="ikode_panel_title">Raiz activa</div>
                            <div class="grid gap-2 text-sm">
                                <div class="xpanel-file-meta"><span>Scope</span><strong>{{ $isAdminFiles ? 'Admin' : 'Cliente' }}</strong></div>
                                <div class="xpanel-file-meta"><span>Directorio</span><strong>{{ $filesDomain ?: 'www/' }}</strong></div>
                                <div class="xpanel-file-meta"><span>Sitios</span><strong>{{ $filesSites->count() }}</strong></div>
                            </div>
                        </div>

                        <div class="ikode_panel">
                            <div class="ikode_panel_title">Preferencias</div>
                            <label class="ikode_setting_row">
                                <span class="ikode_setting_label"><i class="ki-filled ki-eye"></i> Mostrar ocultos</span>
                                <span class="ikode_setting_check"><input type="checkbox" disabled></span>
                            </label>
                            <label class="ikode_setting_row">
                                <span class="ikode_setting_label"><i class="ki-filled ki-code"></i> Word wrap</span>
                                <span class="ikode_setting_check"><input type="checkbox" checked disabled></span>
                            </label>
                            <label class="ikode_setting_row">
                                <span class="ikode_setting_label"><i class="ki-filled ki-shield-tick"></i> Edicion segura</span>
                                <span class="ikode_setting_check"><input type="checkbox" checked disabled></span>
                            </label>
                        </div>

                        <div class="ikode_panel">
                            <div class="ikode_panel_title">Subida</div>
                            <label class="kt-btn kt-btn-primary justify-start cursor-pointer w-full">
                                <i class="ki-filled ki-file-up"></i>
                                Seleccionar archivos
                                <input type="file" id="xpanel_upload_input" class="hidden" multiple>
                            </label>
                            <div class="mt-3 h-1 rounded-full bg-muted overflow-hidden">
                                <div id="xpanel_upload_progress" class="h-full bg-primary transition-all" style="width:0%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <section class="ikode_editor_center bg-background" id="xpanel_center_pane">
                <div class="ikode_tabs border-b border-border">
                    <div class="flex min-w-0" id="xpanel_file_tabs" style="width: 100%; overflow-x: auto;"></div>
                    <div class="ikode_tabs_actions">
                        <button class="ikode_tabs_action_btn" type="button" data-fm-action="duplicate-tab" title="Duplicar pestaña">
                            <i class="ki-filled ki-copy"></i>
                        </button>
                        <button class="ikode_tabs_action_btn" type="button" data-fm-action="save" title="Guardar">
                            <i class="ki-filled ki-check"></i>
                        </button>
                        <button class="ikode_tabs_action_btn" type="button" data-fm-action="download" title="Descargar">
                            <i class="ki-filled ki-exit-down"></i>
                        </button>
                    </div>
                </div>
                <div class="ikode_editor_split">
                    <div class="ikode_editor_codepane border-b border-border" id="xpanel_code_pane">
                        <div id="xpanel_monaco_editor" class="ikode_monaco ikode_hidden"></div>
                        <div id="xpanel_monaco_clone" class="ikode_monaco xpanel-monaco-clone ikode_hidden"></div>
                        <div class="ikode_file_preview ikode_hidden" id="xpanel_file_preview"></div>
                        <div class="xpanel-file-empty" id="xpanel_empty_state">
                            <i class="ki-filled ki-code text-4xl text-muted-foreground"></i>
                            <div>
                                <div class="text-sm font-semibold text-mono">Selecciona un archivo</div>
                                <div class="text-xs text-secondary-foreground mt-1">Puedes editar texto, previsualizar imagenes y guardar con Ctrl+S.</div>
                            </div>
                        </div>
                    </div>

                    <div class="ikode_editor_bottom" id="xpanel_bottom_pane">
                        <div class="ikode_terminal_tabs border-b border-border">
                            <button class="ikode_terminal_tab" type="button" data-console-tab="problems">Problemas</button>
                            <button class="ikode_terminal_tab" type="button" data-console-tab="output">Output</button>
                            <button class="ikode_terminal_tab" type="button" data-console-tab="debug">Debug</button>
                            <button class="ikode_terminal_tab ikode_terminal_tab_active" type="button" data-console-tab="terminal">Terminal</button>
                            <button class="ikode_terminal_tab" type="button" data-console-tab="ports">Ports</button>
                        </div>
                        <div class="ikode_terminal_body ikode_hidden" data-console-view="problems">
                            <div class="xpanel-console-line"><span class="xpanel-console-time">info</span><span class="xpanel-console-text">0 errores criticos detectados en el gestor.</span></div>
                            <div class="xpanel-console-line"><span class="xpanel-console-time">hint</span><span class="xpanel-console-text">Las validaciones reales se conectaran al agente del sitio.</span></div>
                        </div>
                        <div class="ikode_terminal_body ikode_hidden" data-console-view="output">
                            <div class="xpanel-console-line"><span class="xpanel-console-time">xpanel</span><span class="xpanel-console-text">Esperando tareas del sitio {{ $filesDomain ?: 'www/' }}.</span></div>
                            <div id="xpanel_output_log"></div>
                        </div>
                        <div class="ikode_terminal_body ikode_hidden" data-console-view="debug">
                            <div class="xpanel-console-line"><span class="xpanel-console-time">debug</span><span class="xpanel-console-text">Sesion de inspeccion lista.</span></div>
                            <div id="xpanel_bottom_details">Selecciona un archivo para ver detalles.</div>
                        </div>
                        <div data-console-view="terminal">
                            <div class="xpanel-terminal-toolbar">
                                <div class="xpanel-terminal-selector">
                                    <button class="xpanel-terminal-pill" type="button" data-terminal-session="site">
                                        <i class="ki-filled ki-screen"></i>
                                        Terminal 1
                                    </button>
                                    <button class="xpanel-terminal-pill" type="button" data-terminal-session="logs">
                                        <i class="ki-filled ki-document"></i>
                                        Logs
                                    </button>
                                </div>
                                <div class="xpanel-terminal-actions">
                                    <button class="xpanel-terminal-action" type="button" data-terminal-action="new" title="Nueva terminal">
                                        <i class="ki-filled ki-plus"></i>
                                    </button>
                                    <button class="xpanel-terminal-action" type="button" data-terminal-action="clear" title="Limpiar">
                                        <i class="ki-filled ki-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="ikode_terminal_body" id="xpanel_activity_log" data-terminal-view="site">
                                <div><span class="ikode_prompt">xpanel:{{ $filesDomain ?: 'www' }}$</span> gestor listo</div>
                            </div>
                            <div class="ikode_terminal_body ikode_hidden" data-terminal-view="logs">
                                <div><span class="ikode_prompt">logs:{{ $filesDomain ?: 'www' }}$</span> esperando eventos del sitio</div>
                            </div>
                        </div>
                        <div class="ikode_terminal_body ikode_hidden" data-console-view="ports">
                            <div class="xpanel-console-line"><span class="xpanel-console-time">80</span><span class="xpanel-console-text">HTTP del sitio</span></div>
                            <div class="xpanel-console-line"><span class="xpanel-console-time">443</span><span class="xpanel-console-text">HTTPS del sitio</span></div>
                            <div class="xpanel-console-line"><span class="xpanel-console-time">22</span><span class="xpanel-console-text">SSH administrado por el nodo</span></div>
                        </div>
                        <div class="ikode_terminal_body ikode_hidden" data-console-view="preview">
                            <div id="xpanel_inline_preview">Sin vista previa.</div>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="ikode_editor_right border-l border-border ikode_hidden" id="xpanel_right_pane">
                <div class="ikode_left_bottom_tabs border-b border-border ikode_right_tabs">
                    <button class="ikode_left_bottom_tab ikode_left_bottom_tab_active ikode_right_tab" type="button" data-right-tab="info">Info</button>
                    <button class="ikode_left_bottom_tab ikode_right_tab" type="button" data-right-tab="summary">Resumen</button>
                </div>

                <div class="ikode_right_view" data-right-view="info">
                    <div class="ikode_panel">
                        <div class="ikode_panel_title">Elemento seleccionado</div>
                        <div id="xpanel_file_info" class="text-sm text-secondary-foreground">
                            No hay seleccion.
                        </div>
                    </div>
                    <div class="ikode_panel">
                        <div class="ikode_panel_title">Contexto</div>
                        <div class="grid gap-2 text-sm">
                            <div class="xpanel-file-meta"><span>Scope</span><strong>{{ $isAdminFiles ? 'Admin' : 'Cliente' }}</strong></div>
                            <div class="xpanel-file-meta"><span>Raiz</span><strong>{{ $filesDomain ?: 'www/' }}</strong></div>
                            <div class="xpanel-file-meta"><span>Sitios</span><strong>{{ $filesSites->count() }}</strong></div>
                        </div>
                    </div>
                </div>

                <div class="ikode_right_view ikode_hidden" data-right-view="summary">
                    <div class="ikode_panel">
                        <div class="ikode_panel_title">Resumen del gestor</div>
                        <div class="grid gap-2 text-sm text-secondary-foreground">
                            <div class="xpanel-file-meta"><span>Directorio</span><strong id="xpanel_summary_path">/</strong></div>
                            <div class="xpanel-file-meta"><span>Elementos</span><strong id="xpanel_summary_count">0</strong></div>
                            <div class="xpanel-file-meta"><span>Carpetas</span><strong id="xpanel_summary_dirs">0</strong></div>
                            <div class="xpanel-file-meta"><span>Archivos</span><strong id="xpanel_summary_files">0</strong></div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>


<div id="xpanel_ctx_menu" class="fixed hidden z-50 w-48 rounded-md border border-border bg-background shadow-2xl py-1 text-sm overflow-hidden">
    <button type="button" data-fm-action="open" class="w-full text-left px-4 py-2 hover:bg-muted">Abrir / editar</button>
    <button type="button" data-fm-action="rename" class="w-full text-left px-4 py-2 hover:bg-muted">Renombrar</button>
    <button type="button" data-fm-action="download" class="w-full text-left px-4 py-2 hover:bg-muted">Descargar</button>
    <div class="border-t border-border my-1"></div>
    <button type="button" data-fm-action="delete" class="w-full text-left px-4 py-2 hover:bg-destructive/10 text-destructive">Eliminar</button>
</div>

<div id="xpanel_input_modal" class="fixed inset-0 hidden z-50 items-center justify-center bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-sm bg-background border border-border rounded-md p-6 shadow-2xl">
        <h3 id="xpanel_input_title" class="text-base font-semibold text-mono mb-4"></h3>
        <input id="xpanel_input_value" type="text" class="kt-input w-full mb-4">
        <div class="flex gap-2 justify-end">
            <button type="button" class="kt-btn kt-btn-outline" data-input-cancel>Cancelar</button>
            <button type="button" class="kt-btn kt-btn-primary" data-input-confirm>Confirmar</button>
        </div>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('assets/files/split.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.52.2/min/vs/loader.js"></script>
    <script>
        window.XPANEL_FILE_MANAGER_CONFIG = {
            baseUrl: @json($filesBaseUrl),
            domain: @json($filesDomain),
            rootLabel: @json($filesDomain ?: 'www/'),
            scope: @json($isAdminFiles ? 'admin' : 'client'),
        };
    </script>
    <script>
        (() => {
            const config = window.XPANEL_FILE_MANAGER_CONFIG;
            const CSRF = document.querySelector('meta[name="csrf-token"]').content;
            const state = {
                currentPath: '/',
                entries: [],
                selected: null,
                ctxEntry: null,
                editor: null,
                cloneEditor: null,
                openPath: null,
                openName: null,
                isDirty: false,
                inputCallback: null,
            };

            const $ = (selector) => document.querySelector(selector);
            const $$ = (selector) => Array.from(document.querySelectorAll(selector));
            const domainParam = () => encodeURIComponent(config.domain || '');
            const storageKey = `xpanel:files:${config.scope}:${config.domain || 'www'}`;
            const defaultUiState = {
                layout: { left: true, right: false, bottom: true },
                split: { mainThree: [24, 52, 24], mainTwo: [28, 72], center: [68, 32], left: [68, 32] },
                ui: { leftMode: 'explorer', rightTab: 'info', outlineTab: 'outline', consoleTab: 'terminal' },
            };
            const clone = (value) => JSON.parse(JSON.stringify(value));
            const loadUiState = () => {
                try {
                    const raw = localStorage.getItem(storageKey);
                    if (!raw) return clone(defaultUiState);
                    const parsed = JSON.parse(raw);
                    return {
                        layout: {
                            left: parsed?.layout?.left !== false,
                            right: parsed?.layout?.right === true,
                            bottom: parsed?.layout?.bottom !== false,
                        },
                        split: {
                            mainThree: Array.isArray(parsed?.split?.mainThree) ? parsed.split.mainThree : defaultUiState.split.mainThree,
                            mainTwo: Array.isArray(parsed?.split?.mainTwo) ? parsed.split.mainTwo : defaultUiState.split.mainTwo,
                            center: Array.isArray(parsed?.split?.center) ? parsed.split.center : defaultUiState.split.center,
                            left: Array.isArray(parsed?.split?.left) ? parsed.split.left : defaultUiState.split.left,
                        },
                        ui: {
                            leftMode: parsed?.ui?.leftMode || defaultUiState.ui.leftMode,
                            rightTab: parsed?.ui?.rightTab || defaultUiState.ui.rightTab,
                            outlineTab: parsed?.ui?.outlineTab || defaultUiState.ui.outlineTab,
                            consoleTab: parsed?.ui?.consoleTab || defaultUiState.ui.consoleTab,
                        },
                    };
                } catch (error) {
                    return clone(defaultUiState);
                }
            };
            const uiState = loadUiState();
            const persistUiState = () => {
                try {
                    localStorage.setItem(storageKey, JSON.stringify(uiState));
                } catch (error) {
                    console.warn('No se pudo guardar estado del gestor', error);
                }
            };

            const log = (message) => {
                const target = $('#xpanel_activity_log');
                const timeline = $('#xpanel_timeline_list');
                const output = $('#xpanel_output_log');
                const line = document.createElement('div');
                line.innerHTML = `<span class="ikode_prompt">xpanel$</span> ${message}`;
                target.prepend(line);
                if (output) {
                    const out = document.createElement('div');
                    out.className = 'xpanel-console-line';
                    out.innerHTML = `<span class="xpanel-console-time">${new Date().toLocaleTimeString()}</span><span class="xpanel-console-text">${message}</span>`;
                    output.prepend(out);
                }
                if (timeline) {
                    const item = document.createElement('div');
                    item.className = 'ikode_left_item';
                    item.innerHTML = `<i class="ki-filled ki-time"></i><span>${message}</span>`;
                    timeline.prepend(item);
                }
            };

            const toast = (message, type = 'success') => {
                const el = document.createElement('div');
                el.className = `fixed bottom-6 right-6 z-[100] px-5 py-3 rounded-md text-sm font-semibold shadow-2xl ${type === 'error' ? 'bg-destructive/15 border border-destructive/30 text-destructive' : 'bg-green-500/15 border border-green-500/30 text-green-500'}`;
                el.textContent = message;
                document.body.appendChild(el);
                setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 250); }, 2600);
            };

            const api = async (method, endpoint, body = null) => {
                const options = { method, headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' } };
                if (body instanceof FormData) {
                    options.body = body;
                } else if (body) {
                    options.headers['Content-Type'] = 'application/json';
                    options.body = JSON.stringify(body);
                }

                const response = await fetch(config.baseUrl + endpoint, options);
                const contentType = response.headers.get('Content-Type') || '';
                if (!response.ok) {
                    throw new Error(await response.text() || `HTTP ${response.status}`);
                }

                return contentType.includes('application/json') ? response.json() : response;
            };

            const ext = (name) => (name.includes('.') ? name.split('.').pop() : '').toLowerCase();
            const isImage = (name) => /^(png|jpg|jpeg|gif|svg|webp|ico|bmp)$/i.test(ext(name));
            const language = (name) => ({
                php: 'php', js: 'javascript', ts: 'typescript', jsx: 'javascript', tsx: 'typescript',
                html: 'html', htm: 'html', css: 'css', scss: 'css', json: 'json', yml: 'yaml',
                yaml: 'yaml', py: 'python', sh: 'shell', bash: 'shell', md: 'markdown', xml: 'xml',
                sql: 'sql', txt: 'plaintext', env: 'plaintext', gitignore: 'plaintext', htaccess: 'ini',
            })[ext(name)] || 'plaintext';

            const icon = (entry) => {
                if (entry.is_dir) return 'ki-folder';
                if (isImage(entry.name)) return 'ki-picture';
                return ({
                    php: 'ki-code', js: 'ki-js', ts: 'ki-code', html: 'ki-html', css: 'ki-css',
                    json: 'ki-code', md: 'ki-document', sql: 'ki-data', sh: 'ki-setting-2',
                })[ext(entry.name)] || 'ki-document';
            };

            const size = (bytes = 0) => {
                if (bytes < 1024) return `${bytes} B`;
                if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
                return `${(bytes / 1048576).toFixed(1)} MB`;
            };

            const pathJoin = (base, name) => `${base.replace(/\/$/, '')}/${name}`.replace('//', '/');

            const renderInfo = (entry = state.selected) => {
                const box = $('#xpanel_file_info');
                const bottom = $('#xpanel_bottom_details');
                if (!entry) {
                    box.textContent = 'No hay seleccion.';
                    bottom.textContent = 'Selecciona un archivo para ver detalles.';
                    return;
                }

                const html = `
                    <div class="grid gap-1">
                        <div class="xpanel-file-meta"><span>Nombre</span><strong class="text-end break-all">${entry.name}</strong></div>
                        <div class="xpanel-file-meta"><span>Tipo</span><strong>${entry.is_dir ? 'Carpeta' : 'Archivo'}</strong></div>
                        <div class="xpanel-file-meta"><span>Tamano</span><strong>${entry.is_dir ? '-' : size(entry.size || 0)}</strong></div>
                        <div class="xpanel-file-meta"><span>Ruta</span><strong class="text-end break-all">${entry.path}</strong></div>
                        <div class="xpanel-file-meta"><span>Permisos</span><strong>${entry.mode || '-'}</strong></div>
                    </div>
                `;
                box.innerHTML = html;
                bottom.innerHTML = html;
            };

            const renderTabs = () => {
                const tabs = $('#xpanel_file_tabs');
                tabs.innerHTML = state.openPath ? `
                    <button class="ikode_tab ikode_tab_active" type="button">
                        <i class="ki-filled ${icon({ name: state.openName || '', is_dir: false })}"></i>
                        <span>${state.openName}</span>
                        ${state.isDirty ? '<span class="text-warning">*</span>' : ''}
                    </button>
                ` : '';
            };

            const renderList = () => {
                const list = $('#xpanel_file_list');
                const filter = ($('#xpanel_file_filter').value || '').toLowerCase();
                const entries = state.entries.filter((entry) => !filter || entry.name.toLowerCase().includes(filter));
                const dirCount = state.entries.filter((entry) => entry.is_dir).length;
                const fileCount = state.entries.length - dirCount;
                $('#xpanel_summary_path').textContent = state.currentPath;
                $('#xpanel_summary_count').textContent = state.entries.length;
                $('#xpanel_summary_dirs').textContent = dirCount;
                $('#xpanel_summary_files').textContent = fileCount;
                $('#xpanel_outline_count').textContent = `${state.entries.length} elemento(s)`;

                if (!entries.length) {
                    list.innerHTML = `
                        <div class="xpanel-file-drop m-3" id="xpanel_drop_hint">
                            <i class="ki-filled ki-file-up text-lg"></i>
                            <div>Arrastra archivos aqui para subirlos</div>
                            <div class="mt-1 text-[11px] opacity-70">Tambien puedes crear archivos o carpetas desde arriba.</div>
                        </div>
                    `;
                    return;
                }

                list.innerHTML = entries.map((entry) => `
                    <div class="xpanel-file-row ${state.selected?.path === entry.path ? 'active' : ''}"
                         data-path="${entry.path}">
                        <i class="ki-filled ${icon(entry)}"></i>
                        <span class="xpanel-file-name ${entry.is_dir ? 'font-semibold text-mono' : ''}">${entry.name}</span>
                        ${entry.is_dir ? '' : `<span class="xpanel-file-size">${size(entry.size || 0)}</span>`}
                    </div>
                `).join('');

                entries.forEach((entry) => {
                    const row = list.querySelector(`[data-path="${CSS.escape(entry.path)}"]`);
                    row.addEventListener('click', () => select(entry));
                    row.addEventListener('dblclick', () => open(entry));
                    row.addEventListener('contextmenu', (event) => context(event, entry));
                });
            };

            const renderBreadcrumb = () => {
                $('#xpanel_breadcrumb').textContent = state.currentPath;
            };

            const loadDirectory = async (path = '/') => {
                state.currentPath = path || '/';
                renderBreadcrumb();
                $('#xpanel_file_list').innerHTML = '<div class="p-4 text-xs text-secondary-foreground">Cargando...</div>';
                try {
                    const payload = await api('GET', `/list?domain=${domainParam()}&path=${encodeURIComponent(state.currentPath)}`);
                    state.entries = payload.entries || [];
                    renderList();
                    log(`Directorio cargado: ${state.currentPath}`);
                } catch (error) {
                    $('#xpanel_file_list').innerHTML = `<div class="p-4 text-xs text-destructive">${error.message}</div>`;
                    log(`Error: ${error.message}`);
                }
            };

            const select = (entry) => {
                state.selected = entry;
                renderList();
                renderInfo(entry);
            };

            const open = async (entry = state.selected) => {
                if (!entry) return;
                if (entry.is_dir) {
                    await loadDirectory(entry.path);
                    return;
                }
                if (state.isDirty && !confirm('Hay cambios sin guardar. Deseas descartarlos?')) return;

                state.openPath = entry.path;
                state.openName = entry.name;
                state.isDirty = false;
                renderTabs();
                $('#xpanel_empty_state').classList.add('ikode_hidden');
                $('#xpanel_file_preview').classList.add('ikode_hidden');
                $('#xpanel_monaco_editor').classList.remove('ikode_hidden');
                $('#xpanel_monaco_editor').style.display = 'block';
                $('#xpanel_outline_file').textContent = entry.name;

                if (isImage(entry.name)) {
                    $('#xpanel_monaco_editor').classList.add('ikode_hidden');
                    $('#xpanel_monaco_editor').style.display = 'none';
                    $('#xpanel_monaco_clone').classList.add('ikode_hidden');
                    $('#xpanel_file_shell').classList.remove('xpanel-editor-duplicated');
                    $$('[data-fm-action="duplicate-tab"]').forEach((button) => button.classList.remove('is-active'));
                    $('#xpanel_file_preview').classList.remove('ikode_hidden');
                    $('#xpanel_file_preview').innerHTML = `<img src="${config.baseUrl}/download?domain=${domainParam()}&path=${encodeURIComponent(entry.path)}" alt="" style="max-width:100%;max-height:100%;object-fit:contain;">`;
                    $('#xpanel_inline_preview').innerHTML = `Imagen abierta: <span class="font-mono">${entry.name}</span>`;
                    log(`Imagen abierta: ${entry.path}`);
                    return;
                }

                try {
                    const payload = await api('GET', `/read?domain=${domainParam()}&path=${encodeURIComponent(entry.path)}`);
                    const model = monaco.editor.createModel(payload.content || '', language(entry.name));
                    state.editor.setModel(model);
                    if (state.cloneEditor && $('#xpanel_file_shell').classList.contains('xpanel-editor-duplicated')) {
                        state.cloneEditor.setModel(model);
                        $('#xpanel_monaco_clone').classList.remove('ikode_hidden');
                    }
                    layoutEditor();
                    model.onDidChangeContent(() => {
                        if (!state.isDirty) {
                            state.isDirty = true;
                            renderTabs();
                        }
                    });
                    $('#xpanel_inline_preview').textContent = 'Vista previa disponible para imagenes. Este archivo esta en modo editor.';
                    log(`Archivo abierto: ${entry.path}`);
                } catch (error) {
                    toast(error.message, 'error');
                    log(`Error al abrir: ${error.message}`);
                }
            };

            const save = async () => {
                if (!state.openPath || !state.editor) return;
                try {
                    await api('POST', '/write', { domain: config.domain, path: state.openPath, content: state.editor.getValue() });
                    state.isDirty = false;
                    renderTabs();
                    toast('Archivo guardado');
                    log(`Guardado: ${state.openPath}`);
                } catch (error) {
                    toast(error.message, 'error');
                }
            };

            const download = () => {
                const path = state.selected?.is_dir ? state.openPath : (state.selected?.path || state.openPath);
                if (!path) return;
                window.open(`${config.baseUrl}/download?domain=${domainParam()}&path=${encodeURIComponent(path)}`);
            };

            const promptInput = (title, value, callback) => {
                state.inputCallback = callback;
                $('#xpanel_input_title').textContent = title;
                $('#xpanel_input_value').value = value;
                $('#xpanel_input_modal').classList.remove('hidden');
                $('#xpanel_input_modal').classList.add('flex');
                setTimeout(() => $('#xpanel_input_value').focus(), 30);
            };

            const closeInput = () => {
                $('#xpanel_input_modal').classList.add('hidden');
                $('#xpanel_input_modal').classList.remove('flex');
                state.inputCallback = null;
            };

            const newFile = () => promptInput('Nuevo archivo', 'archivo.txt', async (name) => {
                await api('POST', '/write', { domain: config.domain, path: pathJoin(state.currentPath, name), content: '' });
                await loadDirectory(state.currentPath);
                toast('Archivo creado');
            });

            const newFolder = () => promptInput('Nueva carpeta', 'nueva-carpeta', async (name) => {
                await api('POST', '/mkdir', { domain: config.domain, path: pathJoin(state.currentPath, name) });
                await loadDirectory(state.currentPath);
                toast('Carpeta creada');
            });

            const rename = () => {
                const entry = state.selected || state.ctxEntry;
                if (!entry) return;
                promptInput('Renombrar', entry.name, async (name) => {
                    const base = entry.path.substring(0, entry.path.lastIndexOf('/') + 1);
                    await api('POST', '/rename', { domain: config.domain, old_path: entry.path, new_path: base + name });
                    await loadDirectory(state.currentPath);
                    toast('Renombrado');
                });
            };

            const remove = async () => {
                const entry = state.selected || state.ctxEntry;
                if (!entry || !confirm(`Eliminar "${entry.name}"?`)) return;
                await api('POST', '/delete', { domain: config.domain, path: entry.path });
                state.selected = null;
                await loadDirectory(state.currentPath);
                renderInfo(null);
                toast('Eliminado');
            };

            const upload = async (files) => {
                if (!files.length) return;
                let done = 0;
                for (const file of files) {
                    const form = new FormData();
                    form.append('domain', config.domain || '');
                    form.append('path', state.currentPath);
                    form.append('file', file);
                    await api('POST', '/upload', form);
                    done++;
                    $('#xpanel_upload_progress').style.width = `${Math.round((done / files.length) * 100)}%`;
                }
                setTimeout(() => $('#xpanel_upload_progress').style.width = '0%', 700);
                await loadDirectory(state.currentPath);
                toast(`${done} archivo(s) subido(s)`);
            };

            const duplicateTab = () => {
                if (!state.openPath || !state.editor?.getModel()) {
                    toast('Abre un archivo primero', 'error');
                    return;
                }

                const shell = $('#xpanel_file_shell');
                const cloneHost = $('#xpanel_monaco_clone');
                const enabled = !shell.classList.contains('xpanel-editor-duplicated');
                shell.classList.toggle('xpanel-editor-duplicated', enabled);
                cloneHost.classList.toggle('ikode_hidden', !enabled);
                $$('[data-fm-action="duplicate-tab"]').forEach((button) => button.classList.toggle('is-active', enabled));

                if (enabled) {
                    if (!state.cloneEditor) {
                        state.cloneEditor = monaco.editor.create(cloneHost, {
                            value: '',
                            language: 'plaintext',
                            theme: document.documentElement.classList.contains('dark') ? 'vs-dark' : 'vs',
                            fontSize: 14,
                            minimap: { enabled: false },
                            wordWrap: 'on',
                            automaticLayout: true,
                            scrollBeyondLastLine: false,
                            fontFamily: "'JetBrains Mono','Fira Code','Consolas',monospace",
                        });
                    }
                    state.cloneEditor.setModel(state.editor.getModel());
                    log(`Pestana duplicada: ${state.openPath}`);
                }

                layoutEditor();
            };

            const context = (event, entry) => {
                event.preventDefault();
                state.ctxEntry = entry;
                select(entry);
                const menu = $('#xpanel_ctx_menu');
                menu.style.left = `${event.clientX}px`;
                menu.style.top = `${event.clientY}px`;
                menu.classList.remove('hidden');
            };

            const action = async (name) => {
                try {
                    if (name === 'open') await open(state.selected || state.ctxEntry);
                    if (name === 'save') await save();
                    if (name === 'download') download();
                    if (name === 'duplicate-tab') duplicateTab();
                    if (name === 'new-file') newFile();
                    if (name === 'new-folder') newFolder();
                    if (name === 'refresh') await loadDirectory(state.currentPath);
                    if (name === 'rename') rename();
                    if (name === 'delete') await remove();
                } catch (error) {
                    toast(error.message, 'error');
                    log(`Error: ${error.message}`);
                } finally {
                    $('#xpanel_ctx_menu').classList.add('hidden');
                }
            };

            let mainSplit = null;
            let bottomSplit = null;
            let leftSplit = null;

            const layoutEditor = () => {
                window.requestAnimationFrame(() => {
                    state.editor?.layout();
                    state.cloneEditor?.layout();
                });
            };

            const splitVisible = (selector) => {
                const el = $(selector);
                return el && !el.classList.contains('ikode_hidden');
            };

            const resetSplitStyles = () => {
                ['#xpanel_left_pane', '#xpanel_center_pane', '#xpanel_right_pane', '#xpanel_code_pane', '#xpanel_bottom_pane', '#xpanel_left_files_pane', '#xpanel_left_outline_pane'].forEach((selector) => {
                    const el = $(selector);
                    if (!el) return;
                    el.style.width = '';
                    el.style.height = '';
                    el.style.flexBasis = '';
                });
            };

            const saveSplitState = () => {
                if (mainSplit) {
                    const sizes = mainSplit.getSizes();
                    if (splitVisible('#xpanel_right_pane') && splitVisible('#xpanel_left_pane') && sizes.length === 3) {
                        uiState.split.mainThree = sizes;
                    } else if (!splitVisible('#xpanel_right_pane') && splitVisible('#xpanel_left_pane') && sizes.length === 2) {
                        uiState.split.mainTwo = sizes;
                    }
                }
                if (bottomSplit) uiState.split.center = bottomSplit.getSizes();
                if (leftSplit) uiState.split.left = leftSplit.getSizes();
                persistUiState();
            };

            const splitElementStyle = (dimension, size, gutterSize) => ({
                flexBasis: `calc(${size}% - ${gutterSize}px)`,
            });

            const splitGutterStyle = (dimension, gutterSize) => ({
                flexBasis: `${gutterSize}px`,
                flexShrink: '0',
                flexGrow: '0',
            });

            const buildMainSplit = () => {
                if (mainSplit) {
                    mainSplit.destroy();
                    mainSplit = null;
                }

                const panes = ['#xpanel_left_pane', '#xpanel_center_pane', '#xpanel_right_pane'].filter(splitVisible);
                if (!window.Split || panes.length < 2 || window.matchMedia('(max-width: 860px)').matches) {
                    return;
                }

                const sizes = panes.length === 3
                    ? uiState.split.mainThree
                    : (panes.includes('#xpanel_left_pane') ? uiState.split.mainTwo : [76, 24]);
                const minByPane = {
                    '#xpanel_left_pane': 240,
                    '#xpanel_center_pane': 360,
                    '#xpanel_right_pane': 260,
                };

                mainSplit = Split(panes, {
                    sizes,
                    minSize: panes.map((selector) => minByPane[selector]),
                    gutterSize: 6,
                    elementStyle: splitElementStyle,
                    gutterStyle: splitGutterStyle,
                    snapOffset: 0,
                    onDrag: layoutEditor,
                    onDragEnd: () => {
                        saveSplitState();
                        layoutEditor();
                    },
                });
            };

            const buildBottomSplit = () => {
                if (bottomSplit) {
                    bottomSplit.destroy();
                    bottomSplit = null;
                }

                if (!window.Split || !splitVisible('#xpanel_bottom_pane')) {
                    $('#xpanel_code_pane').style.flexBasis = '';
                    return;
                }

                bottomSplit = Split(['#xpanel_code_pane', '#xpanel_bottom_pane'], {
                    direction: 'vertical',
                    sizes: uiState.split.center,
                    minSize: [240, 120],
                    gutterSize: 6,
                    elementStyle: splitElementStyle,
                    gutterStyle: splitGutterStyle,
                    snapOffset: 0,
                    onDrag: layoutEditor,
                    onDragEnd: () => {
                        saveSplitState();
                        layoutEditor();
                    },
                });
            };

            const buildLeftSplit = () => {
                if (leftSplit) {
                    leftSplit.destroy();
                    leftSplit = null;
                }

                if (!window.Split || !splitVisible('#xpanel_left_pane') || uiState.ui.leftMode !== 'explorer') {
                    return;
                }

                leftSplit = Split(['#xpanel_left_files_pane', '#xpanel_left_outline_pane'], {
                    direction: 'vertical',
                    sizes: uiState.split.left,
                    minSize: [120, 110],
                    gutterSize: 6,
                    elementStyle: splitElementStyle,
                    gutterStyle: splitGutterStyle,
                    snapOffset: 0,
                    onDragEnd: saveSplitState,
                });
            };

            const rebuildLayout = () => {
                resetSplitStyles();
                buildMainSplit();
                buildBottomSplit();
                buildLeftSplit();
                layoutEditor();
            };

            const syncLayoutButtons = () => {
                $$('[data-layout-toggle]').forEach((button) => {
                    const pane = button.dataset.layoutToggle;
                    const target = pane === 'bottom' ? '#xpanel_bottom_pane' : `#xpanel_${pane}_pane`;
                    button.classList.toggle('ikode_header_btn_active', splitVisible(target));
                });
            };

            const toggleLayoutPane = (pane) => {
                const target = pane === 'bottom' ? '#xpanel_bottom_pane' : `#xpanel_${pane}_pane`;
                const el = $(target);
                if (!el) return;

                uiState.layout[pane] = !uiState.layout[pane];
                el.classList.toggle('ikode_hidden', !uiState.layout[pane]);
                if (pane === 'bottom') {
                    $('#xpanel_code_pane').classList.toggle('border-b', uiState.layout[pane]);
                }
                persistUiState();
                syncLayoutButtons();
                rebuildLayout();
            };

            const toggleFullscreen = (button) => {
                $('#xpanel_file_shell').classList.toggle('xpanel-fullscreen');
                button.classList.toggle('ikode_header_btn_active', $('#xpanel_file_shell').classList.contains('xpanel-fullscreen'));
                rebuildLayout();
            };

            const switchLeftMode = (mode) => {
                uiState.ui.leftMode = mode;
                $$('[data-left-mode]').forEach((button) => {
                    button.classList.toggle('ikode_header_btn_active', button.dataset.leftMode === mode);
                });
                $$('[data-left-panel]').forEach((panel) => {
                    panel.classList.toggle('ikode_hidden', panel.dataset.leftPanel !== mode);
                });
                persistUiState();
                rebuildLayout();
            };

            const switchOutlineTab = (tab) => {
                uiState.ui.outlineTab = tab;
                $$('[data-outline-tab]').forEach((button) => {
                    button.classList.toggle('ikode_left_bottom_tab_active', button.dataset.outlineTab === tab);
                });
                $$('[data-outline-view]').forEach((view) => {
                    view.classList.toggle('ikode_hidden', view.dataset.outlineView !== tab);
                });
                persistUiState();
            };

            const switchConsoleTab = (tab) => {
                uiState.ui.consoleTab = tab;
                $$('[data-console-tab]').forEach((button) => {
                    button.classList.toggle('ikode_terminal_tab_active', button.dataset.consoleTab === tab);
                });
                $$('[data-console-view]').forEach((view) => {
                    view.classList.toggle('ikode_hidden', view.dataset.consoleView !== tab);
                });
                persistUiState();
            };

            const switchRightTab = (tab) => {
                uiState.ui.rightTab = tab;
                $$('[data-right-tab]').forEach((button) => {
                    button.classList.toggle('ikode_left_bottom_tab_active', button.dataset.rightTab === tab);
                });
                $$('[data-right-view]').forEach((view) => {
                    view.classList.toggle('ikode_hidden', view.dataset.rightView !== tab);
                });
                persistUiState();
            };

            const switchTerminalSession = (session) => {
                $$('[data-terminal-session]').forEach((button) => {
                    button.classList.toggle('ikode_header_btn_active', button.dataset.terminalSession === session);
                });
                $$('[data-terminal-view]').forEach((view) => {
                    view.classList.toggle('ikode_hidden', view.dataset.terminalView !== session);
                });
            };

            const terminalAction = (action) => {
                if (action === 'clear') {
                    $('#xpanel_activity_log').innerHTML = `<div><span class="ikode_prompt">xpanel:${config.rootLabel.replace('/', '')}$</span> limpio</div>`;
                    return;
                }
                if (action === 'new') {
                    const count = $$('[data-terminal-session]').length + 1;
                    const selector = document.querySelector('.xpanel-terminal-selector');
                    const button = document.createElement('button');
                    button.className = 'xpanel-terminal-pill';
                    button.type = 'button';
                    button.dataset.terminalSession = `terminal-${count}`;
                    button.innerHTML = `<i class="ki-filled ki-screen"></i> Terminal ${count}`;
                    selector.appendChild(button);

                    const view = document.createElement('div');
                    view.className = 'ikode_terminal_body ikode_hidden';
                    view.dataset.terminalView = `terminal-${count}`;
                    view.innerHTML = `<div><span class="ikode_prompt">xpanel:${config.rootLabel.replace('/', '')}$</span> nueva terminal</div>`;
                    $('#xpanel_activity_log').parentElement.appendChild(view);
                    button.addEventListener('click', () => switchTerminalSession(button.dataset.terminalSession));
                    switchTerminalSession(button.dataset.terminalSession);
                }
            };

            const applyStoredLayout = () => {
                $('#xpanel_left_pane').classList.toggle('ikode_hidden', !uiState.layout.left);
                $('#xpanel_right_pane').classList.toggle('ikode_hidden', !uiState.layout.right);
                $('#xpanel_bottom_pane').classList.toggle('ikode_hidden', !uiState.layout.bottom);
                $('#xpanel_code_pane').classList.toggle('border-b', uiState.layout.bottom);
                syncLayoutButtons();
                switchLeftMode(uiState.ui.leftMode);
                switchOutlineTab(uiState.ui.outlineTab);
                switchConsoleTab(uiState.ui.consoleTab);
                switchRightTab(uiState.ui.rightTab);
                switchTerminalSession('site');
            };

            window.XPanelFM = {
                dragOver(event) {
                    event.preventDefault();
                    ($('#xpanel_drop_hint') || $('#xpanel_file_list')).classList.add('dragover');
                },
                dragLeave() {
                    ($('#xpanel_drop_hint') || $('#xpanel_file_list')).classList.remove('dragover');
                },
                async drop(event) {
                    event.preventDefault();
                    ($('#xpanel_drop_hint') || $('#xpanel_file_list')).classList.remove('dragover');
                    await upload(Array.from(event.dataTransfer.files || []));
                },
            };

            $$('[data-fm-action]').forEach((button) => button.addEventListener('click', () => action(button.dataset.fmAction)));
            $$('[data-left-mode]').forEach((button) => button.addEventListener('click', () => switchLeftMode(button.dataset.leftMode)));
            $$('[data-layout-toggle]').forEach((button) => button.addEventListener('click', () => toggleLayoutPane(button.dataset.layoutToggle)));
            $$('[data-layout-action="fullscreen"]').forEach((button) => button.addEventListener('click', () => toggleFullscreen(button)));
            $$('[data-outline-tab]').forEach((button) => button.addEventListener('click', () => switchOutlineTab(button.dataset.outlineTab)));
            $$('[data-console-tab]').forEach((button) => button.addEventListener('click', () => switchConsoleTab(button.dataset.consoleTab)));
            $$('[data-right-tab]').forEach((button) => button.addEventListener('click', () => switchRightTab(button.dataset.rightTab)));
            $$('[data-terminal-session]').forEach((button) => button.addEventListener('click', () => switchTerminalSession(button.dataset.terminalSession)));
            $$('[data-terminal-action]').forEach((button) => button.addEventListener('click', () => terminalAction(button.dataset.terminalAction)));

            $('#xpanel_site_jump')?.addEventListener('change', (event) => {
                window.location.href = event.target.value;
            });
            $('#xpanel_file_filter').addEventListener('input', renderList);
            $('#xpanel_upload_input').addEventListener('change', (event) => upload(Array.from(event.target.files || [])));
            $('[data-input-cancel]').addEventListener('click', closeInput);
            $('[data-input-confirm]').addEventListener('click', async () => {
                const value = $('#xpanel_input_value').value.trim();
                const callback = state.inputCallback;
                closeInput();
                if (value && callback) {
                    try { await callback(value); } catch (error) { toast(error.message, 'error'); }
                }
            });
            document.addEventListener('click', (event) => {
                if (!event.target.closest('#xpanel_ctx_menu')) $('#xpanel_ctx_menu').classList.add('hidden');
            });
            document.addEventListener('keydown', (event) => {
                if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
                    event.preventDefault();
                    save();
                }
            });

            require.config({ paths: { vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@0.52.2/min/vs' } });
            require(['vs/editor/editor.main'], () => {
                state.editor = monaco.editor.create($('#xpanel_monaco_editor'), {
                    value: '',
                    language: 'plaintext',
                    theme: document.documentElement.classList.contains('dark') ? 'vs-dark' : 'vs',
                    fontSize: 14,
                    minimap: { enabled: false },
                    wordWrap: 'on',
                    automaticLayout: true,
                    scrollBeyondLastLine: false,
                    fontFamily: "'JetBrains Mono','Fira Code','Consolas',monospace",
                });
                $('#xpanel_monaco_editor').classList.add('ikode_hidden');
                $('#xpanel_monaco_editor').style.display = 'none';
                $('#xpanel_monaco_clone').classList.add('ikode_hidden');
                new MutationObserver(() => {
                    monaco.editor.setTheme(document.documentElement.classList.contains('dark') ? 'vs-dark' : 'vs');
                }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                applyStoredLayout();
                loadDirectory('/');
            });

            applyStoredLayout();
            window.addEventListener('resize', rebuildLayout);
        })();
    </script>
@endpush
