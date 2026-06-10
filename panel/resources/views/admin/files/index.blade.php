@extends('layouts.app')

@section('content')
<div class="space-y-4">
    {{-- Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.sites.index') }}" class="text-gray-500 hover:text-white transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-black tracking-tight flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                </svg>
                Archivos [Admin] — <span class="text-indigo-400 font-mono">{{ $site->domain }}</span>
                @if($site->tenant)
                    <span class="text-xs text-gray-500 font-normal">({{ $site->tenant->name }})</span>
                @endif
            </h1>
        </div>
    </div>

    {{-- File Manager Layout --}}
    <div class="flex rounded-2xl border border-white/10 overflow-hidden"
         style="height: calc(100vh - 10rem);">

        {{-- Sidebar: Directory Tree --}}
        <aside id="fm-tree" class="w-52 shrink-0 border-r border-white/10 bg-black/30 overflow-y-auto flex flex-col">
            <div class="p-3 border-b border-white/10">
                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Directorio</div>
            </div>
            <div id="tree-root" class="flex-1 py-2 text-sm"></div>
        </aside>

        {{-- Center: File List --}}
        <div class="w-64 shrink-0 border-r border-white/10 flex flex-col bg-[#0d0e11]">
            {{-- Toolbar --}}
            <div class="border-b border-white/10 p-2 flex flex-wrap gap-1.5">
                <button onclick="FM.newFile()" title="Nuevo archivo"
                    class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs bg-white/5 hover:bg-white/10 text-gray-300 hover:text-white transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Archivo
                </button>
                <button onclick="FM.newFolder()" title="Nueva carpeta"
                    class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs bg-white/5 hover:bg-white/10 text-gray-300 hover:text-white transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path></svg>
                    Carpeta
                </button>
                <label title="Subir archivo"
                    class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs bg-indigo-500/15 hover:bg-indigo-500/25 text-indigo-300 cursor-pointer transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Subir
                    <input type="file" id="upload-input" class="hidden" multiple onchange="FM.uploadFiles(this.files)">
                </label>
            </div>

            {{-- Breadcrumb --}}
            <div id="fm-breadcrumb" class="px-3 py-2 border-b border-white/10 text-xs text-gray-500 truncate font-mono">/</div>

            {{-- File list --}}
            <div id="file-list" class="flex-1 overflow-y-auto"
                 ondragover="event.preventDefault(); this.classList.add('bg-indigo-500/5')"
                 ondragleave="this.classList.remove('bg-indigo-500/5')"
                 ondrop="FM.handleDrop(event)">
                <div id="file-list-inner" class="py-1"></div>
            </div>

            <div id="upload-bar" class="hidden p-3 border-t border-white/10">
                <div class="text-xs text-gray-400 mb-1.5">Subiendo...</div>
                <div class="h-1 rounded-full bg-white/10 overflow-hidden">
                    <div id="upload-progress" class="h-full bg-indigo-500 transition-all duration-300" style="width:0%"></div>
                </div>
            </div>
        </div>

        {{-- Editor --}}
        <div class="flex-1 flex flex-col min-w-0">
            <div class="border-b border-white/10 px-4 py-2 flex items-center justify-between bg-[#0d0e11]">
                <div class="flex items-center gap-3">
                    <span id="editor-filename" class="text-sm font-mono text-gray-400">Sin archivo abierto</span>
                    <span id="editor-dirty" class="hidden text-xs px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 font-semibold">● Sin guardar</span>
                </div>
                <div class="flex items-center gap-2">
                    <button id="btn-download" onclick="FM.downloadCurrent()" class="hidden px-3 py-1.5 rounded-lg text-xs bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white transition">Descargar</button>
                    <button id="btn-save" onclick="FM.save()" class="hidden px-4 py-1.5 rounded-lg text-xs font-semibold bg-indigo-500/20 hover:bg-indigo-500/30 text-indigo-300 hover:text-indigo-100 transition">Guardar</button>
                </div>
            </div>
            <div class="flex-1 relative">
                <div id="monaco-container" class="absolute inset-0"></div>
                <div id="image-preview" class="absolute inset-0 hidden flex items-center justify-center bg-[#1e1e1e] p-4">
                    <img id="preview-img" src="" alt="" class="max-w-full max-h-full object-contain rounded-lg">
                </div>
                <div id="editor-placeholder" class="absolute inset-0 flex flex-col items-center justify-center text-gray-600">
                    <svg class="w-16 h-16 mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                    </svg>
                    <p class="text-sm">Selecciona un archivo para editarlo</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="ctx-menu" class="fixed hidden z-50 w-48 rounded-xl border border-white/10 bg-[#13141a] shadow-2xl py-1 text-sm overflow-hidden">
    <button onclick="FM.ctxOpen()" class="w-full text-left px-4 py-2 hover:bg-white/5 text-white/80">Abrir / Editar</button>
    <button onclick="FM.ctxRename()" class="w-full text-left px-4 py-2 hover:bg-white/5 text-white/80">Renombrar</button>
    <button onclick="FM.ctxDownload()" class="w-full text-left px-4 py-2 hover:bg-white/5 text-white/80">Descargar</button>
    <div class="border-t border-white/10 my-1"></div>
    <button onclick="FM.ctxDelete()" class="w-full text-left px-4 py-2 hover:bg-red-500/10 text-red-400">Eliminar</button>
</div>

<div id="input-modal" class="fixed inset-0 hidden z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-sm bg-[#13141a] border border-white/10 rounded-2xl p-6 shadow-2xl">
        <h3 id="input-modal-title" class="text-lg font-bold text-white mb-4"></h3>
        <input id="input-modal-value" type="text"
            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white outline-none focus:border-indigo-500/50 mb-4"
            onkeydown="if(event.key==='Enter') FM.inputConfirm(); if(event.key==='Escape') FM.inputCancel();">
        <div class="flex gap-3 justify-end">
            <button onclick="FM.inputCancel()" class="px-4 py-2 rounded-xl border border-white/10 text-sm text-white/60 hover:bg-white/5 transition">Cancelar</button>
            <button onclick="FM.inputConfirm()" class="px-4 py-2 rounded-xl bg-indigo-500/20 text-sm font-semibold text-indigo-300 hover:bg-indigo-500/30 transition">Confirmar</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.52.2/min/vs/loader.js"></script>
<script>
const CSRF   = document.querySelector('meta[name="csrf-token"]').content;
const BASE   = '{{ url("/admin/files/" . $site->id . "/api") }}';
const DOMAIN = '{{ $site->domain }}';
const state  = { currentPath:'/', selectedEntry:null, ctxEntry:null, editor:null, isDirty:false, openPath:null, inputCallback:null };

async function api(method, endpoint, body=null) {
    const opts = { method, headers: { 'X-CSRF-TOKEN': CSRF, 'Accept':'application/json' } };
    if (body instanceof FormData) { opts.body = body; }
    else if (body) { opts.headers['Content-Type']='application/json'; opts.body=JSON.stringify(body); }
    const res = await fetch(BASE+endpoint, opts);
    if (!res.ok) { const t=await res.text(); throw new Error(t||`HTTP ${res.status}`); }
    const ct=res.headers.get('Content-Type')||'';
    if (ct.includes('application/json')) return res.json();
    return res;
}
function ext(n){return n.split('.').pop().toLowerCase();}
function isImage(n){return /^(png|jpg|jpeg|gif|svg|webp|ico|bmp)$/i.test(ext(n));}
function langFor(n){const m={php:'php',js:'javascript',ts:'typescript',jsx:'javascript',tsx:'typescript',html:'html',htm:'html',css:'css',scss:'css',json:'json',yml:'yaml',yaml:'yaml',py:'python',sh:'shell',bash:'shell',md:'markdown',xml:'xml',sql:'sql',txt:'plaintext',env:'plaintext',gitignore:'plaintext',htaccess:'ini'};return m[ext(n)]||'plaintext';}
function fileIcon(e){if(e.is_dir)return'📁';const x=ext(e.name);if(isImage(e.name))return'🖼️';const i={php:'🐘',js:'📜',ts:'📘',html:'🌐',css:'🎨',json:'📋',md:'📝',sql:'🗄️',sh:'⚙️',py:'🐍',env:'🔧',xml:'📄'};return i[x]||'📄';}
function formatSize(b){if(b<1024)return b+'B';if(b<1048576)return(b/1024).toFixed(1)+'K';return(b/1048576).toFixed(1)+'M';}
function showToast(msg,type='success'){const el=document.createElement('div');el.className=`fixed bottom-6 right-6 z-[100] px-5 py-3 rounded-2xl text-sm font-semibold shadow-2xl transition-all duration-300 ${type==='error'?'bg-red-500/20 border border-red-500/30 text-red-200':'bg-emerald-500/20 border border-emerald-500/30 text-emerald-200'}`;el.textContent=msg;document.body.appendChild(el);setTimeout(()=>{el.style.opacity='0';setTimeout(()=>el.remove(),300);},3000);}

async function loadDirectory(path){state.currentPath=path||'/';document.getElementById('fm-breadcrumb').textContent=state.currentPath;const inner=document.getElementById('file-list-inner');inner.innerHTML='<div class="px-4 py-8 text-center text-gray-600 text-xs">Cargando...</div>';try{const data=await api('GET',`/list?domain=${encodeURIComponent(DOMAIN)}&path=${encodeURIComponent(path)}`);renderFileList(data.entries||[]);renderTree(path);}catch(e){inner.innerHTML=`<div class="px-4 py-8 text-center text-red-400 text-xs">Error: ${e.message}</div>`;}}
function renderFileList(entries){const inner=document.getElementById('file-list-inner');if(!entries.length){inner.innerHTML='<div class="px-4 py-8 text-center text-gray-600 text-xs">Carpeta vacía</div>';return;}inner.innerHTML=entries.map(e=>`<div class="flex items-center gap-2.5 px-3 py-2 cursor-pointer hover:bg-white/5 rounded-lg mx-1 group transition select-none ${state.selectedEntry?.path===e.path?'bg-white/5 text-white':'text-gray-400'}" data-path="${e.path}" data-isdir="${e.is_dir}" data-name="${e.name}" onclick="FM.selectEntry(this,${JSON.stringify(e).replace(/"/g,'&quot;')})" ondblclick="FM.openEntry(${JSON.stringify(e).replace(/"/g,'&quot;')})" oncontextmenu="FM.showCtxMenu(event,${JSON.stringify(e).replace(/"/g,'&quot;')})"><span class="text-base leading-none">${fileIcon(e)}</span><span class="flex-1 min-w-0 text-xs truncate ${e.is_dir?'font-semibold text-white/80':''}">${e.name}</span>${!e.is_dir?`<span class="text-[10px] text-gray-600 shrink-0">${formatSize(e.size)}</span>`:''}</div>`).join('');}
function renderTree(activePath){const root=document.getElementById('tree-root');const segments=activePath.split('/').filter(Boolean);const paths=['/'];segments.forEach((s,i)=>paths.push('/'+segments.slice(0,i+1).join('/')));root.innerHTML=paths.map((p,d)=>`<div class="flex items-center gap-1.5 px-3 py-1.5 cursor-pointer text-xs rounded-lg mx-1 transition select-none ${p===activePath?'bg-white/5 text-white font-semibold':'text-gray-500 hover:text-gray-300 hover:bg-white/5'}" style="padding-left:${12+d*12}px" onclick="loadDirectory('${p}')"><span>📂</span>${d===0?'/':p.split('/').pop()}</div>`).join('');}

const FM = {
    selectEntry(el,e){document.querySelectorAll('[data-path]').forEach(x=>x.classList.remove('bg-white/5','text-white'));el.classList.add('bg-white/5','text-white');state.selectedEntry=e;},
    openEntry(e){if(e.is_dir)loadDirectory(e.path);else this.openFile(e);},
    async openFile(entry){if(state.isDirty&&!confirm('Cambios sin guardar. ¿Descartar?'))return;state.openPath=entry.path;state.isDirty=false;document.getElementById('editor-filename').textContent=entry.name;document.getElementById('editor-dirty').classList.add('hidden');document.getElementById('btn-save').classList.remove('hidden');document.getElementById('btn-download').classList.remove('hidden');document.getElementById('editor-placeholder').classList.add('hidden');if(isImage(entry.name)){document.getElementById('monaco-container').style.display='none';const ip=document.getElementById('image-preview');ip.classList.remove('hidden');ip.style.display='flex';document.getElementById('preview-img').src=BASE+`/download?domain=${encodeURIComponent(DOMAIN)}&path=${encodeURIComponent(entry.path)}`;return;}document.getElementById('image-preview').classList.add('hidden');document.getElementById('image-preview').style.display='none';document.getElementById('monaco-container').style.display='block';try{const data=await api('GET',`/read?domain=${encodeURIComponent(DOMAIN)}&path=${encodeURIComponent(entry.path)}`);if(state.editor){const model=monaco.editor.createModel(data.content||'',langFor(entry.name));state.editor.setModel(model);state.editor.getModel().onDidChangeContent(()=>{if(!state.isDirty){state.isDirty=true;document.getElementById('editor-dirty').classList.remove('hidden');}});}}catch(e){showToast('Error: '+e.message,'error');}},
    async save(){if(!state.openPath||!state.editor)return;try{await api('POST','/write',{domain:DOMAIN,path:state.openPath,content:state.editor.getValue()});state.isDirty=false;document.getElementById('editor-dirty').classList.add('hidden');showToast('Guardado');}catch(e){showToast(e.message,'error');}},
    downloadCurrent(){if(state.openPath)window.open(BASE+`/download?domain=${encodeURIComponent(DOMAIN)}&path=${encodeURIComponent(state.openPath)}`);},
    newFile(){showInputModal('Nombre del nuevo archivo','archivo.txt',async(name)=>{try{await api('POST','/write',{domain:DOMAIN,path:state.currentPath.replace(/\/$/,'')+'/'+name,content:''});await loadDirectory(state.currentPath);showToast('Archivo creado');}catch(e){showToast(e.message,'error');}});},
    newFolder(){showInputModal('Nombre de la carpeta','nueva-carpeta',async(name)=>{try{await api('POST','/mkdir',{domain:DOMAIN,path:state.currentPath.replace(/\/$/,'')+'/'+name});await loadDirectory(state.currentPath);showToast('Carpeta creada');}catch(e){showToast(e.message,'error');}});},
    async uploadFiles(files){const bar=document.getElementById('upload-bar');const prog=document.getElementById('upload-progress');bar.classList.remove('hidden');let done=0;for(const f of files){const fd=new FormData();fd.append('domain',DOMAIN);fd.append('path',state.currentPath);fd.append('file',f);try{await api('POST','/upload',fd);done++;prog.style.width=Math.round((done/files.length)*100)+'%';}catch(e){showToast('Error: '+e.message,'error');}}setTimeout(()=>{bar.classList.add('hidden');prog.style.width='0%';},1000);await loadDirectory(state.currentPath);showToast(`${done} archivo(s) subido(s)`);document.getElementById('upload-input').value='';},
    handleDrop(e){e.preventDefault();e.currentTarget.classList.remove('bg-indigo-500/5');const files=Array.from(e.dataTransfer.files);if(files.length)this.uploadFiles(files);},
    showCtxMenu(e,entry){e.preventDefault();state.ctxEntry=entry;const m=document.getElementById('ctx-menu');m.style.left=e.clientX+'px';m.style.top=e.clientY+'px';m.classList.remove('hidden');},
    ctxOpen(){document.getElementById('ctx-menu').classList.add('hidden');if(state.ctxEntry)this.openEntry(state.ctxEntry);},
    ctxRename(){document.getElementById('ctx-menu').classList.add('hidden');const entry=state.ctxEntry;if(!entry)return;showInputModal('Renombrar',entry.name,async(name)=>{const dir=entry.path.substring(0,entry.path.lastIndexOf('/')+1);try{await api('POST','/rename',{domain:DOMAIN,old_path:entry.path,new_path:dir+name});await loadDirectory(state.currentPath);showToast('Renombrado');}catch(e){showToast(e.message,'error');}});},
    ctxDownload(){document.getElementById('ctx-menu').classList.add('hidden');if(state.ctxEntry&&!state.ctxEntry.is_dir)window.open(BASE+`/download?domain=${encodeURIComponent(DOMAIN)}&path=${encodeURIComponent(state.ctxEntry.path)}`);},
    async ctxDelete(){document.getElementById('ctx-menu').classList.add('hidden');const entry=state.ctxEntry;if(!entry||!confirm(`¿Eliminar "${entry.name}"?`))return;try{await api('POST','/delete',{domain:DOMAIN,path:entry.path});await loadDirectory(state.currentPath);showToast('Eliminado');}catch(e){showToast(e.message,'error');}},
    inputConfirm(){const val=document.getElementById('input-modal-value').value.trim();document.getElementById('input-modal').classList.add('hidden');if(val&&state.inputCallback)state.inputCallback(val);state.inputCallback=null;},
    inputCancel(){document.getElementById('input-modal').classList.add('hidden');state.inputCallback=null;},
};
function showInputModal(title,def,cb){document.getElementById('input-modal-title').textContent=title;const inp=document.getElementById('input-modal-value');inp.value=def;document.getElementById('input-modal').classList.remove('hidden');inp.focus();inp.select();state.inputCallback=cb;}
document.addEventListener('click',(e)=>{if(!e.target.closest('#ctx-menu'))document.getElementById('ctx-menu').classList.add('hidden');});
document.addEventListener('keydown',(e)=>{if((e.ctrlKey||e.metaKey)&&e.key==='s'){e.preventDefault();FM.save();}});
require.config({paths:{vs:'https://cdn.jsdelivr.net/npm/monaco-editor@0.52.2/min/vs'}});
require(['vs/editor/editor.main'],function(){const isDark=document.documentElement.classList.contains('dark');state.editor=monaco.editor.create(document.getElementById('monaco-container'),{value:'',language:'plaintext',theme:isDark?'vs-dark':'vs',fontSize:14,minimap:{enabled:false},wordWrap:'on',automaticLayout:true,scrollBeyondLastLine:false,fontFamily:"'JetBrains Mono','Fira Code','Consolas',monospace"});new MutationObserver(()=>{monaco.editor.setTheme(document.documentElement.classList.contains('dark')?'vs-dark':'vs');}).observe(document.documentElement,{attributes:true,attributeFilter:['class']});loadDirectory('/');});
</script>
@endpush
