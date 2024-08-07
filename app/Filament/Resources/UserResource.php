<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Support\Services\UserService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $navigationLabel = 'المستخدمين';

    protected static ?string $label = 'مستخدم';

    protected static ?string $pluralLabel = 'المستخدمين';

    protected static ?string $navigationGroup = 'البيانات الأساسية';

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم المستخدم')
                    ->string()
                    ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('username')
                    ->label('اسم المستخدم')
                    ->unique('users', 'username', $form->getRecord())
                    ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('password')
                    ->label('كلمة السر')
                    ->password()
                    ->markAsRequired($form->getOperation() === 'create')
                    ->rules($form->getOperation() === 'create' ? 'required' : 'nullable')
                    ->maxLength(191),
                Forms\Components\Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label('الفرع'),
                Forms\Components\Select::make('role')
                    ->options(UserService::JOBS)
                    ->label('الوظيفة')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المستخدم')
                    ->searchable(),
                Tables\Columns\TextColumn::make('username')
                    ->label('اسم المستخدم')
                    ->searchable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('الفرع')
                    ->placeholder('مستخدم عام')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('role')
                    ->formatStateUsing(fn(User $user) => UserService::JOBS[$user->role])
                    ->label('الوظيفة')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        if ($data['password'] === null) {
                            unset($data['password']);
                        }

                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('auto_login')
                    ->color(Color::Slate)
                    ->label('تسجيل الدخول')
                    ->icon('heroicon-o-lock-closed')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        auth()->logout();

                        session()->invalidate();
                        session()->regenerateToken();

                        auth()->loginUsingId($record->id);

                        session()->regenerate();

                        return redirect(route('filament.admin.pages.dashboard'));
                    })
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUsers::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->role === 'manager';
    }
}
