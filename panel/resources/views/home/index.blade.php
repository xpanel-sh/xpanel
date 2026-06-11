@extends('layouts.home')

@section('title', 'XPanel — Hosting Panel')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-24 text-center">
    <h1 class="text-4xl font-bold text-mono mb-4">Potencia tu hosting</h1>
    <p class="text-lg text-muted-foreground mb-10 max-w-xl mx-auto">
        Gestiona tus sitios web, bases de datos, dominios y correos desde un solo lugar.
    </p>
    <a href="{{ route('client.login') }}"
       class="kt-btn kt-btn-primary kt-btn-lg">
        Acceder a mi panel
    </a>
</div>
@endsection
