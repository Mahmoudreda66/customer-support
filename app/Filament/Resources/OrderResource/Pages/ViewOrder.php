<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Models\SystemLog;
use App\Models\User;
use App\Support\Services\OrderService;
use App\Support\Services\UserService;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ViewOrder extends Page implements HasTable
{
    use InteractsWithRecord, InteractsWithTable;

    protected static string $resource = OrderResource::class;

    protected static string $view = 'filament.resources.order-resource.pages.view-order';

    public string $activeTab = 'order';

    public function mount(Order $record): void
    {
        $this->record = $this->resolveRecord($record->id);
    }

    public function getTitle(): string|Htmlable
    {
        return 'عرض الطلب #'.$this->record->id;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SystemLog::query()
                    ->whereHas('orders', fn (Builder $builder) => $builder->where('orders.id', $this->record->id))
                    ->latest()
            )
            ->paginated(false)
            ->selectable()
            ->columns([
                TextColumn::make('user.name')
                    ->label('تم بواسطة'),
                TextColumn::make('user.role')
                    ->formatStateUsing(fn (User $user) => $user->role ? UserService::JOBS[$user->role] : 'موظف عام')
                    ->label('نوع الموظف'),
                TextColumn::make('user.branch.name')
                    ->placeholder('موظف عام')
                    ->label('فرع الموظف'),
                TextColumn::make('data.status')
                    ->formatStateUsing(fn ($record) => OrderService::STATUSES[$record->data['status']])
                    ->badge()
                    ->color(fn (string $state): string => OrderService::colors($state))
                    ->label('حالة الطلب'),
                TextColumn::make('data.description')
                    ->limit(50)
                    ->html()
                    ->placeholder('لا يوجد')
                    ->label('وصف الطلب'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->label('تاريخ العملية'),
            ])
            ->filters([
                // ...
            ])
            ->actions([
                ViewAction::make()
                    ->recordTitle('التفاصيل')
                    ->label('عرض التفاصيل')
                    ->form([
                        RichEditor::make('data.description')
                            ->label('الوصف')
                            ->disabled(),
                    ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->pluralModelLabel('العمليات'),
                ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            OrderService::changeStatusAction(false),
            EditAction::make()
                ->url(route('filament.admin.resources.orders.edit', $this->record->id))
                ->icon('heroicon-o-pencil-square'),
            DeleteAction::make()
                ->after(function () {
                    return to_route('filament.admin.resources.orders.index');
                })
                ->icon('heroicon-o-trash'),
        ];
    }
}
