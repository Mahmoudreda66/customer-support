@php($tabCounts = $this->tabCounts)
<x-filament-panels::page>
    <x-filament::tabs>
        <x-filament::tabs.item
            :active="$activeTab === 'stale'"
            wire:click="$set('activeTab', 'stale')"
        >
            الطلبات المعلقة / المتوقفة
            <x-filament::badge color="danger" class="ms-1">
                {{ $tabCounts['stale'] }}
            </x-filament::badge>
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'old_handed'"
            wire:click="$set('activeTab', 'old_handed')"
        >
            تم التسليم منذ أسبوع فأكثر
            <x-filament::badge color="warning" class="ms-1">
                {{ $tabCounts['old_handed'] }}
            </x-filament::badge>
        </x-filament::tabs.item>
    </x-filament::tabs>

    {{ $this->table }}
</x-filament-panels::page>
