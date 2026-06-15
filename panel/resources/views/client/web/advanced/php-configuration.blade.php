@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Avanzado',
        'title' => 'Configuracion PHP',
        'description' => 'Ajusta version, extensiones y directivas PHP del sitio.',
        'metrics' => [
            ['label' => 'Version', 'value' => $site->php_version ?? '8.2', 'icon' => 'ki-code'],
            ['label' => 'Memory limit', 'value' => '128M', 'icon' => 'ki-chart'],
            ['label' => 'Upload max', 'value' => '64M', 'icon' => 'ki-file-up'],
        ],
        'cards' => [
            ['title' => 'Directivas', 'body' => 'memory_limit, upload_max_filesize y max_execution_time se gestionaran aqui.'],
            ['title' => 'Extensiones', 'body' => 'Activa o desactiva extensiones PHP por sitio.'],
        ],
    ])
@endsection
