<?php

namespace App\Filament\Resources\ComplainResource\Pages;

use App\Filament\Resources\ComplainResource;
use App\Models\Complain;
use App\Models\ComplainNote;
use App\Support\Services\ComplainService;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class ViewComplain extends Page implements HasTable
{
    use InteractsWithRecord, InteractsWithTable;

    protected static string $resource = ComplainResource::class;

    protected static string $view = 'filament.resources.complain-resource.pages.view-complain';

    public string $activeTab = 'complain';

    public function mount(Complain $record): void
    {
        $this->record = $this->resolveRecord($record->id);
    }

    public function getTitle(): string|Htmlable
    {
        return 'عرض الشكوى #'.$this->record->id;
    }

    public function table(Table $table): Table
    {
        return $table->query(ComplainNote::query()->where('complain_id', $this->record->id)->latest())
            ->columns([
                TextColumn::make('user.name')
                    ->placeholder('غير معروف')
                    ->label('المستخدم'),
                TextColumn::make('created_at')
                    ->date()
                    ->label('تاريخ الإنشاء'),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (ComplainNote $note) => ['open' => 'success', 'closed' => 'danger'][$note->type])
                    ->formatStateUsing(fn (ComplainNote $note) => $note->type === 'open' ? 'مفتوحة' : 'تم الإغلاق')
                    ->label('الحالة'),
            ])->actions([
                ViewAction::make()
                    ->recordTitle('التفاصيل')
                    ->label('عرض التفاصيل')
                    ->form([
                        RichEditor::make('complain')
                            ->label('المشكلة')
                            ->disabled(),
                        RichEditor::make('solution')
                            ->label('الحل')
                            ->disabled(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            ComplainService::addNoteAction(false),
            EditAction::make()
                ->url(route('filament.admin.resources.complains.edit', $this->record->id))
                ->icon('heroicon-o-pencil-square'),
            \Filament\Actions\DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ];
    }
}
