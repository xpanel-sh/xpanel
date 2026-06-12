@extends('layouts.admin')

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&amp;_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
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
                    </div>
                </div>
            </main>

            @include('layouts.partials.admin.footer')
        </div>
    </div>
@endsection
