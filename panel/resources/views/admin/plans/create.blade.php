@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-4xl">
        <div class="mb-8">
            <a href="{{ route('admin.plans.index') }}" class="text-sm text-gray-400 hover:text-white">Volver a planes</a>
            <h1 class="mt-4 text-3xl font-black">Crear Plan de Hosting</h1>
            <p class="mt-2 text-gray-400">Define límites iniciales para nuevas cuentas cliente.</p>
        </div>

        <form action="{{ route('admin.plans.store') }}" method="POST">
            @include('admin.plans._form')
        </form>
    </section>
@endsection
