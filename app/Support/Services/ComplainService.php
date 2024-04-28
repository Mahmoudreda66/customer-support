<?php

namespace App\Support\Services;

use App\Models\Complain;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class ComplainService
{
    const TYPES = [
        'open' => 'مفتوحة',
        'closed' => 'تم الإغلاق',
    ];

    public static function colors($state): string
    {
        return match ($state) {
            'open' => 'success',
            'closed' => 'danger',
        };
    }

    public static function addNoteAction(bool $for_table = true): \Filament\Actions\Action|Action
    {
        if ($for_table) {
            $action = Action::make('updateStatus');
        } else {
            $action = \Filament\Actions\Action::make('updateStatus');
        }

        return $action
            ->icon('heroicon-o-plus-circle')
            ->color('success')
            ->label('إضافة ملاحظة')
            ->form([
                RichEditor::make('complain')
                    ->label('الشكوى')
                    ->required(),
                RichEditor::make('solution')
                    ->label('الحل')
                    ->required(),
                Select::make('type')
                    ->label('حالة الشكوى')
                    ->options(ComplainService::TYPES)
                    ->default(fn(Complain $complain) => $complain->type)
                    ->required()
            ])
            ->action(function (array $data, Complain $complain) {
                $complain->notes()->create(array_merge($data, ['user_id' => auth()->id()]));

                if ($complain->type != $data['type']) {
                    $complain->update(['type' => $data['type']]);
                }

                Notification::make()
                    ->title('تم حفظ الملاحظة بنجاح')
                    ->success()
                    ->send();
            });
    }
}
