@php use App\Support\Services\OrderService; @endphp
<x-filament-panels::page>

    <x-filament::tabs>
        <x-filament::tabs.item
                :active="$activeTab === 'order'"
                wire:click="$set('activeTab', 'order')">
            بيانات الطلب
        </x-filament::tabs.item>

        @if(in_array(auth()->user()->role, ['manager', 'data_entry', 'customer_support']))
            <x-filament::tabs.item
                    :active="$activeTab === 'customer'"
                    wire:click="$set('activeTab', 'customer')">
                بيانات العميل
            </x-filament::tabs.item>
        @endif
    </x-filament::tabs>

    <x-filament::section>
        <x-slot name="heading">
            بيانات {{ $activeTab === 'customer' ? 'العميل' : 'الطلب' }}
        </x-slot>

        @if($activeTab === 'customer')
            <ul class="mb-5">
                <li class="mb-2">
                    اسم العميل: {{ $record->machine->customer->name }}
                </li>
                <li class="mb-2">
                    رقم الهاتف: {{ $record->machine->customer->phone }}
                </li>
                <li class="mb-2">
                    الفرع التابع: {{ $record->machine->customer->branch->name }}
                </li>
                <li class="mb-2">
                    سيريال العميل: {{ $record->machine->customer->serial_number ?? 'لا يوجد' }}
                </li>
                <li class="mb-2">
                    العنوان: {{ $record->machine->customer->address }}
                </li>
                <li class="mb-2">
                    الوصف: {{ $record->machine->customer->description ?? 'لا يوجد' }}
                </li>
            </ul>
            <x-filament::button
                    outlined
                    icon="heroicon-o-user-circle"
                    :href="route('filament.admin.resources.customers.view', $record->machine->customer_id)"
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
                    نوع الطلب: {{ OrderService::TYPES[$record->type] }}
                </li>
                <li class="mb-2">
                    وقت الإنتهاء المٌقدر: {{ $record->deadline ?? 'غير محدد' }}
                </li>
                <li class="mb-2">
                    سيريال الماكينة: {{ $record->machine->serial_number ?? 'غير معروف' }}
                </li>
                <li class="mb-2">
                    نوع الماكينة: {{ $record->machine->machineType?->name ?? 'غير معروف' }}
                </li>
                <li class="mb-2">
                    موديل الماكينة: {{ $record->machine->machineModel?->model ?? 'غير معروف' }}
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
            تست الصيانة
        </x-slot>

        <div style="display: flex; width: 100%">
            <div style="width: 50%; margin-left: 20px">
                <x-filament::fieldset>
                    <x-slot name="label">
                        قبل الصيانة
                    </x-slot>

                    @if($image_before = $record->image_before)
                        <a href="{{ asset('storage/' . $image_before) }}" target="_blank">
                            <img src="{{ asset('storage/' . $image_before) }}" alt="تست قبل الصيانة">
                        </a>
                    @else
                        <div style="text-align: center">
                            لا توجد صور حتى الآن
                        </div>
                    @endif
                </x-filament::fieldset>
            </div>
            <div style="width: 50%">
                <x-filament::fieldset>
                    <x-slot name="label">
                        بعد الصيانة
                    </x-slot>

                    @if($image_after = $record->image_after)
                        <a href="{{ asset('storage/' . $image_after) }}" target="_blank">
                            <img src="{{ asset('storage/' . $image_after) }}" alt="تست بعد الصيانة">
                        </a>
                    @else
                        <div style="text-align: center">
                            لا توجد صور حتى الآن
                        </div>
                    @endif
                </x-filament::fieldset>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">
            ملحقات الماكينة
        </x-slot>

        <div style="display: flex; justify-content: space-between">
            <label>
                <x-filament::input.checkbox disabled :checked="$record->dorg === 1"/>
                <span>
                &nbsp; درج
            </span>
            </label>
            <label>
                <x-filament::input.checkbox disabled :checked="$record->ink === 1"/>
                <span>
                &nbsp; حبر
            </span>
            </label>
            <label>
                <x-filament::input.checkbox disabled :checked="$record->magnetic === 1"/>
                <span>
                &nbsp; مغناطيس
            </span>
            </label>
            <label>
                <x-filament::input.checkbox disabled :checked="$record->duplex === 1"/>
                <span>
                &nbsp; دوبلكس
            </span>
            </label>
            <label>
                <x-filament::input.checkbox disabled :checked="$record->shelf === 1"/>
                <span>
                &nbsp; رف
            </span>
            </label>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">
            العمليات
        </x-slot>

        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
