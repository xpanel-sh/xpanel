@extends('layouts.client')

@php
    $selectedTemplateId = old('template_id');
@endphp

@section('content')
<div class="flex grow rounded-xl bg-background border border-input lg:ms-(--sidebar-width) mt-0 lg:mt-(--header-height) m-5"
     x-data="{
         mode: '{{ $selectedTemplateId ? 'template' : 'custom' }}',
         selectedTemplate: null,
         templates: @js($templates->keyBy('id')),
         composeYaml: @js(old('compose_yaml', '')),
         params: @js(old('params', [])),
         selectTemplate(id) {
             this.selectedTemplate = this.templates[id] ?? null;
             if (this.selectedTemplate) {
                 this.composeYaml = this.selectedTemplate.compose_template;
                 this.params = {};
             }
         }
     }">
    <div class="flex flex-col grow kt-scrollable-y-auto lg:[--kt-scrollbar-width:auto] pt-5" id="scrollable_content">
        <main class="grow" role="content">
            <div class="kt-container-fluid">
                <div class="grid gap-5 lg:gap-7.5">

                    {{-- Cabecera --}}
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h1 class="text-2xl font-semibold text-mono">Nueva app Docker</h1>
                            <p class="mt-1 text-sm text-secondary-foreground">Despliega una app desde un template o sube tu propio compose.</p>
                        </div>
                        <a href="{{ route('client.docker.index') }}" class="kt-btn kt-btn-outline">
                            Volver
                        </a>
                    </div>

                    @if($errors->any())
                        <div class="rounded-xl border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form action="{{ route('client.docker.store') }}" method="POST">
                        @csrf

                        {{-- Modo: template vs custom --}}
                        <div class="grid sm:grid-cols-2 gap-4 mb-5">
                            <button type="button"
                                class="kt-card p-5 text-left border-2 transition-colors"
                                :class="mode === 'template' ? 'border-primary' : 'border-transparent hover:border-border'"
                                @click="mode = 'template'">
                                <i class="ki-filled ki-cube-3 text-2xl mb-2 block text-primary"></i>
                                <p class="font-semibold text-mono">Desde template</p>
                                <p class="text-xs text-secondary-foreground mt-1">Elige una app preconfigurada e ingresa solo los parámetros.</p>
                            </button>
                            <button type="button"
                                class="kt-card p-5 text-left border-2 transition-colors"
                                :class="mode === 'custom' ? 'border-primary' : 'border-transparent hover:border-border'"
                                @click="mode = 'custom'; selectedTemplate = null">
                                <i class="ki-filled ki-code text-2xl mb-2 block text-primary"></i>
                                <p class="font-semibold text-mono">Docker Compose custom</p>
                                <p class="text-xs text-secondary-foreground mt-1">Pega o escribe tu propio docker-compose.yml.</p>
                            </button>
                        </div>

                        {{-- Datos básicos --}}
                        <div class="kt-card mb-5">
                            <div class="kt-card-header min-h-12">
                                <h2 class="kt-card-title text-base">Información general</h2>
                            </div>
                            <div class="kt-card-content grid sm:grid-cols-2 gap-4 p-5">
                                <div class="grid gap-1.5">
                                    <label class="text-sm font-medium text-mono">Nombre visible</label>
                                    <input class="kt-input" type="text" name="name"
                                        value="{{ old('name') }}" required maxlength="60"
                                        placeholder="Mi PostgreSQL">
                                </div>
                                <div class="grid gap-1.5">
                                    <label class="text-sm font-medium text-mono">Identificador <span class="text-secondary-foreground font-normal">(slug)</span></label>
                                    <input class="kt-input font-mono" type="text" name="slug"
                                        value="{{ old('slug') }}" required
                                        pattern="[a-z0-9][a-z0-9-]{0,48}[a-z0-9]"
                                        placeholder="mi-postgres">
                                    <p class="text-xs text-secondary-foreground">Solo minúsculas, números y guiones.</p>
                                </div>
                                <div class="grid gap-1.5">
                                    <label class="text-sm font-medium text-mono">Dominio público <span class="text-secondary-foreground font-normal">(opcional)</span></label>
                                    <input class="kt-input" type="text" name="domain"
                                        value="{{ old('domain') }}" placeholder="app.midominio.com">
                                    <p class="text-xs text-secondary-foreground">Si se define en el compose, Traefik lo expondrá automáticamente.</p>
                                </div>
                            </div>
                        </div>

                        {{-- Selector de template --}}
                        <div x-show="mode === 'template'" class="kt-card mb-5">
                            <div class="kt-card-header min-h-12">
                                <h2 class="kt-card-title text-base">Selecciona una app</h2>
                            </div>
                            <div class="kt-card-content p-5">
                                @if($templates->isEmpty())
                                    <p class="text-sm text-secondary-foreground">No hay templates disponibles aún. Usa el modo Custom.</p>
                                @else
                                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                        @foreach($templates->groupBy('category') as $category => $group)
                                            <div class="col-span-full">
                                                <p class="text-xs font-semibold text-secondary-foreground uppercase tracking-wider mb-2">{{ ucfirst($category) }}</p>
                                            </div>
                                            @foreach($group as $template)
                                            <label class="flex items-start gap-3 rounded-xl border border-border bg-accent/30 px-4 py-3 cursor-pointer hover:bg-accent transition-colors"
                                                   :class="selectedTemplate?.id == {{ $template->id }} ? 'border-primary/50 bg-primary/10' : ''">
                                                <input type="radio" name="template_id" value="{{ $template->id }}"
                                                    class="mt-0.5"
                                                    @change="selectTemplate({{ $template->id }})"
                                                    {{ old('template_id') == $template->id ? 'checked' : '' }}>
                                                <div>
                                                    <p class="font-semibold text-sm text-mono">{{ $template->name }}</p>
                                                    @if($template->description)
                                                        <p class="text-xs text-secondary-foreground mt-0.5">{{ $template->description }}</p>
                                                    @endif
                                                </div>
                                            </label>
                                            @endforeach
                                        @endforeach
                                    </div>

                                    {{-- Parámetros del template seleccionado --}}
                                    <div x-show="selectedTemplate && selectedTemplate.parameters?.length" class="mt-5">
                                        <p class="text-sm font-semibold text-mono mb-3">Configuración de la app</p>
                                        <div class="grid sm:grid-cols-2 gap-4">
                                            <template x-for="param in selectedTemplate?.parameters ?? []" :key="param.key">
                                                <div class="grid gap-1.5">
                                                    <label class="text-sm font-medium text-mono" x-text="param.label"></label>
                                                    <input
                                                        class="kt-input"
                                                        :type="param.type === 'password' ? 'password' : 'text'"
                                                        :name="'params[' + param.key + ']'"
                                                        :placeholder="param.default ?? ''"
                                                        :required="param.required ?? false"
                                                        x-model="params[param.key]">
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Editor YAML --}}
                        <div class="kt-card mb-5">
                            <div class="kt-card-header min-h-12 flex items-center justify-between">
                                <h2 class="kt-card-title text-base">
                                    <span x-show="mode === 'template'">Compose generado <span class="text-secondary-foreground font-normal text-xs">(editable)</span></span>
                                    <span x-show="mode === 'custom'">docker-compose.yml</span>
                                </h2>
                            </div>
                            <div class="kt-card-content p-5">
                                <textarea
                                    name="compose_yaml"
                                    class="kt-input font-mono text-xs w-full"
                                    rows="20"
                                    required
                                    placeholder="version: '3.8'&#10;services:&#10;  myapp:&#10;    image: ..."
                                    x-model="composeYaml"></textarea>
                                <p class="text-xs text-secondary-foreground mt-2">
                                    Usa <code class="bg-muted px-1 rounded">{{VARIABLE}}</code> para interpolar parámetros desde templates.
                                    Variables de sistema: <code class="bg-muted px-1 rounded">{{TENANT_CODE}}</code>, <code class="bg-muted px-1 rounded">{{SLUG}}</code>, <code class="bg-muted px-1 rounded">{{CONTAINER_NAME}}</code>.
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button type="submit" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-check"></i>
                                Crear y arrancar app
                            </button>
                            <a href="{{ route('client.docker.index') }}" class="kt-btn kt-btn-outline">Cancelar</a>
                        </div>
                    </form>

                </div>
            </div>
        </main>
        @include('layouts.partials.client.footer')
    </div>
</div>
@endsection
