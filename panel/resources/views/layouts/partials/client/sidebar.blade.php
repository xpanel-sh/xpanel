@php
    $sideItems = [
        ['label' => 'Dashboard', 'route' => 'client.dashboard', 'match' => ['client.dashboard'], 'icon' => 'ki-element-11'],
        ['label' => 'Sitios', 'route' => 'client.sites.index', 'match' => ['client.sites.*', 'client.files.*'], 'icon' => 'ki-click'],
        ['label' => 'Bases de datos', 'route' => 'client.databases.index', 'match' => ['client.databases.*'], 'icon' => 'ki-data'],
        ['label' => 'Dominios', 'route' => 'client.domains.index', 'match' => ['client.domains.*'], 'icon' => 'ki-click'],
        ['label' => 'DNS', 'route' => 'client.dns.index', 'match' => ['client.dns.*'], 'icon' => 'ki-cloud'],
        ['label' => 'Correos', 'route' => 'client.emails.index', 'match' => ['client.emails.*'], 'icon' => 'ki-sms'],
        ['label' => 'Cuenta', 'route' => 'client.account.show', 'match' => ['client.account.*'], 'icon' => 'ki-user'],
    ];

    $sideBaseClass = 'kt-btn kt-btn-ghost kt-btn-icon rounded-full size-10 border border-transparent text-secondary-foreground hover:bg-background hover:[&_i]:text-primary hover:border-input';
    $sideActiveClass = 'bg-background [&_i]:text-primary border-input active';
@endphp

<!-- Sidebar -->
<div class="fixed w-(--sidebar-width) lg:top-(--header-height) top-0 bottom-0 z-20 hidden lg:flex flex-col items-stretch shrink-0 group py-3 lg:py-0 [--kt-drawer-enable:true] lg:[--kt-drawer-enable:false]"
     data-kt-drawer="true"
     data-kt-drawer-class="kt-drawer kt-drawer-start top-0 bottom-0"
     id="sidebar">
    <div class="flex grow shrink-0" id="sidebar_content">
        <div class="kt-scrollable-y-auto grow gap-2.5 shrink-0 flex items-center flex-col max-h-[calc(100dvh-10px)] lg:max-h-[calc(100dvh-70px)] pt-2">
            @foreach($sideItems as $item)
                @php
                    $active = collect($item['match'])->contains(fn ($pattern) => request()->routeIs($pattern));
                @endphp

                <a class="{{ $sideBaseClass }} {{ $active ? $sideActiveClass : '' }}"
                   data-kt-tooltip=""
                   data-kt-tooltip-placement="right"
                   href="{{ route($item['route']) }}">
                    <span class="kt-menu-icon">
                        <i class="ki-filled {{ $item['icon'] }} text-lg"></i>
                    </span>
                    <span class="kt-tooltip" data-kt-tooltip-content="true">
                        {{ $item['label'] }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</div>
<!-- End of Sidebar -->
