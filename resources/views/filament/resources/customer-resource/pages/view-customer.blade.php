<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            بيانات العميل
        </x-slot>

        <ul class="mb-5">
            <li class="mb-2">
                اسم العميل: {{ $record->name }}
            </li>
            <li class="mb-2">
                رقم الهاتف: {{ $record->phone }}
            </li>
            <li class="mb-2">
                الفرع التابع: {{ $record->branch->name }}
            </li>
            <li class="mb-2">
                سيريال العميل: {{ $record->serial_number ?? 'لا يوجد' }}
            </li>
            <li class="mb-2">
                العنوان: {{ $record->address }}
            </li>
            <li class="mb-2">
                الوصف: {{ $record->description ?? 'لا يوجد' }}
            </li>
        </ul>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">
            الطلبات السابقة
        </x-slot>

        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
