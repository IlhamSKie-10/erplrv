<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' — ERP Produksi' : 'ERP Produksi' }}</title>
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
    <script src="{{ mix('js/app.js') }}" defer></script>
    @livewireStyles
</head>
<body class="h-full" x-data>

{{-- ─── Flash Message (global) ───────────────────── --}}
<div
    x-data="{ show: false, message: '', type: 'success' }"
    x-init="
        $watch('$store.flash.message', val => {
            if (val) { message = val; type = $store.flash.type; show = true; }
            else { show = false; }
        });
    "
    x-show="show"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-[-0.5rem]"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed top-4 right-4 z-50 min-w-72 max-w-sm"
    style="display: none"
>
    <div
        :class="{
            'alert-success': type === 'success',
            'alert-danger':  type === 'error',
            'alert-warning': type === 'warning',
            'alert-info':    type === 'info',
        }"
        class="alert shadow-lg"
    >
        <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                x-show="type === 'success'"
                d="M5 13l4 4L19 7" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                x-show="type === 'error' || type === 'warning'"
                d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
        </svg>
        <span x-text="message" class="flex-1 text-sm"></span>
        <button @click="$store.flash.clear()" class="ml-2 opacity-60 hover:opacity-100">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
</div>

<div class="layout-sidebar">

    {{-- ─── Sidebar ──────────────────────────────── --}}
    <aside class="sidebar" x-show="$store.sidebar.open" x-cloak>
        {{-- Logo / Brand --}}
        <div class="px-4 py-4 border-b border-border">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-md bg-accent flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2v-4M9 21H5a2 2 0 01-2-2v-4m0 0h18"/>
                    </svg>
                </div>
                <span class="font-semibold text-sm text-foreground">ERP Produksi</span>
            </a>
        </div>

        {{-- User info --}}
        @auth
        <div class="px-4 py-3 border-b border-border">
            <p class="text-xs text-muted-foreground">Masuk sebagai</p>
            <p class="text-sm font-medium text-foreground truncate">{{ auth()->user()->full_name }}</p>
            <p class="text-xs text-muted-foreground">{{ auth()->user()->primaryRoleCode() }}</p>
        </div>
        @endauth

        {{-- Navigation --}}
        <nav class="px-2 py-3 space-y-0.5">

            {{-- Dashboard (all roles) --}}
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-2.5 px-3 py-2 rounded-md text-sm transition-colors
                      {{ request()->routeIs('dashboard') ? 'bg-accent text-white' : 'text-foreground hover:bg-panel-strong' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>

            {{-- CS Module --}}
            @if(in_array(auth()->user()?->primaryRoleCode(), ['CS','SUPER_ADMIN','DEVELOPER']))
            <a href="{{ route('cs.index') }}"
               class="flex items-center gap-2.5 px-3 py-2 rounded-md text-sm transition-colors
                      {{ request()->routeIs('cs.*') ? 'bg-accent text-white' : 'text-foreground hover:bg-panel-strong' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Pesanan (CS)
            </a>
            @endif

            {{-- Designer Module --}}
            @if(in_array(auth()->user()?->primaryRoleCode(), ['DESIGNER','SUPER_ADMIN','DEVELOPER']))
            <a href="{{ route('designer.index') }}"
               class="flex items-center gap-2.5 px-3 py-2 rounded-md text-sm transition-colors
                      {{ request()->routeIs('designer.*') ? 'bg-accent text-white' : 'text-foreground hover:bg-panel-strong' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                </svg>
                Antrian Desain
            </a>
            @endif

            {{-- Production Module --}}
            @if(in_array(auth()->user()?->primaryRoleCode(), ['PRODUCTION','MANAGER','SUPER_ADMIN','DEVELOPER']))
            <a href="{{ route('production.index') }}"
               class="flex items-center gap-2.5 px-3 py-2 rounded-md text-sm transition-colors
                      {{ request()->routeIs('production.*') ? 'bg-accent text-white' : 'text-foreground hover:bg-panel-strong' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
                Produksi
            </a>

            <a href="{{ route('progress.index') }}"
               class="flex items-center gap-2.5 px-3 py-2 rounded-md text-sm transition-colors
                      {{ request()->routeIs('progress.*') ? 'bg-accent text-white' : 'text-foreground hover:bg-panel-strong' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Progress
            </a>

            <a href="{{ route('priority.index') }}"
               class="flex items-center gap-2.5 px-3 py-2 rounded-md text-sm transition-colors
                      {{ request()->routeIs('priority.*') ? 'bg-accent text-white' : 'text-foreground hover:bg-panel-strong' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"/>
                </svg>
                Prioritas
            </a>
            @endif

            {{-- Separator --}}
            <div class="h-px bg-border my-2"></div>

            {{-- Notifications (all roles) --}}
            <a href="{{ route('notifications.index') }}"
               class="flex items-center gap-2.5 px-3 py-2 rounded-md text-sm transition-colors
                      {{ request()->routeIs('notifications.*') ? 'bg-accent text-white' : 'text-foreground hover:bg-panel-strong' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                Notifikasi
            </a>

            <a href="{{ route('reminders.index') }}"
               class="flex items-center gap-2.5 px-3 py-2 rounded-md text-sm transition-colors
                      {{ request()->routeIs('reminders.*') ? 'bg-accent text-white' : 'text-foreground hover:bg-panel-strong' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Pengingat
            </a>

            {{-- Admin/Reports (elevated roles only) --}}
            @if(in_array(auth()->user()?->primaryRoleCode(), ['SUPER_ADMIN','MANAGER','DEVELOPER']))
            <div class="h-px bg-border my-2"></div>

            <a href="{{ route('activity.index') }}"
               class="flex items-center gap-2.5 px-3 py-2 rounded-md text-sm transition-colors
                      {{ request()->routeIs('activity.*') ? 'bg-accent text-white' : 'text-foreground hover:bg-panel-strong' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                Log Aktivitas
            </a>

            <a href="{{ route('performance.index') }}"
               class="flex items-center gap-2.5 px-3 py-2 rounded-md text-sm transition-colors
                      {{ request()->routeIs('performance.*') ? 'bg-accent text-white' : 'text-foreground hover:bg-panel-strong' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Performa
            </a>

            <a href="/admin"
               class="flex items-center gap-2.5 px-3 py-2 rounded-md text-sm transition-colors text-foreground hover:bg-panel-strong">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Admin Panel
            </a>
            @endif
        </nav>

        {{-- Bottom: logout --}}
        <div class="absolute bottom-0 left-0 right-0 border-t border-border p-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-md text-sm text-muted-foreground hover:bg-danger-soft hover:text-danger transition-colors">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Keluar
                </button>
            </form>
        </div>
    </aside>

    {{-- ─── Main content area ─────────────────────── --}}
    <div class="main-content" :class="{ 'sidebar-collapsed': !$store.sidebar.open }">

        {{-- Top bar --}}
        <header class="sticky top-0 z-30 bg-panel border-b border-border px-4 py-2.5 flex items-center gap-3">
            {{-- Sidebar toggle --}}
            <button @click="$store.sidebar.toggle()"
                class="p-1.5 rounded-md text-muted-foreground hover:bg-panel-strong hover:text-foreground transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            {{-- Page title slot --}}
            <h1 class="text-sm font-semibold text-foreground flex-1">
                {{ $title ?? 'Dashboard' }}
            </h1>

            {{-- Poll indicator --}}
            <span class="poll-dot" title="Sinkronisasi aktif"></span>
        </header>

        {{-- Page content --}}
        <main class="flex-1 p-3 md:p-4">
            {{ $slot }}
        </main>

        {{-- Footer --}}
        <footer class="px-6 py-3 border-t border-border text-xs text-muted-foreground text-right">
            ERP Produksi &copy; {{ date('Y') }}
        </footer>
    </div>

</div>

@livewireScripts
<script>
    // Sync Livewire events to Alpine flash store
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('notify', ({ message, type }) => {
            Alpine.store('flash').show(message, type ?? 'success');
        });
    });
</script>
</body>
</html>
