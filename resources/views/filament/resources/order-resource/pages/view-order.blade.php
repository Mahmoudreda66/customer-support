@php use App\Support\Services\OrderService; @endphp
<x-filament-panels::page>

    <x-filament::tabs>
        <x-filament::tabs.item
            :active="$activeTab === 'order'"
            wire:click="$set('activeTab', 'order')">
            بيانات الطلب
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'customer'"
            wire:click="$set('activeTab', 'customer')">
            بيانات العميل
        </x-filament::tabs.item>
    </x-filament::tabs>

    <x-filament::section>
        <x-slot name="heading">
            بيانات {{ $activeTab === 'customer' ? 'العميل' : 'الطلب' }}
        </x-slot>

        @if($activeTab === 'customer')
            <ul class="mb-5">
                <li class="mb-2">
                    اسم العميل: {{ $record->customer->name }}
                </li>
                <li class="mb-2">
                    رقم الهاتف: {{ $record->customer->phone }}
                </li>
                <li class="mb-2">
                    الفرع التابع: {{ $record->customer->branch->name }}
                </li>
                <li class="mb-2">
                    سيريال العميل: {{ $record->customer->serial_number ?? 'لا يوجد' }}
                </li>
                <li class="mb-2">
                    العنوان: {{ $record->customer->address }}
                </li>
                <li class="mb-2">
                    الوصف: {{ $record->customer->description ?? 'لا يوجد' }}
                </li>
            </ul>
            <x-filament::button
                outlined
                icon="heroicon-o-user-circle"
                :href="route('filament.admin.resources.customers.view', $record->customer_id)"
                tag="a">
                عرض ملف العميل
            </x-filament::button>
        @else
            <x-filament::badge :color="OrderService::colors($record->status)" size="lg" class="mb-5"
                               style="padding: .75rem 0; font-size: 0.85rem;">
                {{ OrderService::STATUSES[$record->status] }}
            </x-filament::badge>
            <ul>
                <li class="mb-2">
                    مُنشئ الطلب: {{ $record->user->name }}
                </li>
                <li class="mb-2">
                    الفرع: {{ $record->branch->name }}
                </li>
                <li class="mb-2">
                    وقت الإنتهاء المٌقدر: {{ $record->deadline ?? 'غير محدد' }}
                </li>
                <li class="mb-2">
                    سيريال الماكينة: {{ $record->serial_number ?? 'غير معروف' }}
                </li>
                <li class="mb-2">
                    نوع الماكينة: {{ $record->machineType?->name ?? 'غير معروف' }}
                </li>
                <li class="mb-2">
                    موديل الماكينة: {{ $record->machine_model ?? 'غير معروف' }}
                </li>
                <li class="mb-2">
                    موظف الصيانة: {{ $record->repairer_engineer?->name ?? 'لم يتم التكليف' }}
                </li>
                <hr style="margin: 20px 0">
                {!! $record->description ?? 'لا يوجد' !!}
            </ul>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">
            العمليات
        </x-slot>

        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
