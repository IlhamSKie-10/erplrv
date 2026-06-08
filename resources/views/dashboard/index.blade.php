<x-layouts.app title="Dashboard">

    <div wire:poll.15s>
        @livewire('dashboard.dashboard-metrics')
    </div>

</x-layouts.app>
