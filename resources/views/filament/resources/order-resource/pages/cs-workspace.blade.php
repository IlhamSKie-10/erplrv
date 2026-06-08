<x-filament-panels::page>
    <div class="flex flex-col xl:flex-row gap-6 items-start">
        
        <!-- Sisi Kiri: Form -->
        <div class="w-full xl:w-1/3 shrink-0">
            <x-filament::section>
                <x-slot name="heading">
                    Input Pesanan Baru
                </x-slot>

                <form wire:submit="saveOrder" class="space-y-6">
                    {{ $this->form }}

                    <div class="flex justify-end mt-4">
                        <x-filament::button type="submit" color="primary">
                            Simpan Pesanan Baru
                        </x-filament::button>
                    </div>
                </form>
            </x-filament::section>
        </div>

        <!-- Sisi Kanan: Tabel -->
        <div class="w-full xl:w-2/3 overflow-hidden">
            <x-filament::section>
                <x-slot name="heading">
                    Daftar Pesanan
                </x-slot>
                {{ $this->table }}
            </x-filament::section>
        </div>
        
    </div>
</x-filament-panels::page>
