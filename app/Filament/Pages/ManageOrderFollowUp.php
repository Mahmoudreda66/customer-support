<?php

namespace App\Filament\Pages;

use App\Filament\Resources\OrderResource;
use App\Filament\Support\OrderTableBuilder;
use App\Models\Order;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;

class ManageOrderFollowUp extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-pause-circle';

    protected static string $view = 'filament.pages.manage-order-follow-up';

    protected static ?string $navigationLabel = 'متابعة الطلبات';

    protected static ?string $pluralLabel = 'متابعة الطلبات';

    protected static ?string $navigationGroup = 'الدعم الفني';

    protected static ?int $navigationSort = -1;

    protected static ?string $title = 'متابعة الطلبات';

    public ?string $activeTab = 'stale';

    public function mount(): void
    {
        $this->mountInteractsWithTable();
    }

    public function updatedActiveTab(): void
    {
        unset($this->tabCounts);
        $this->resetTable();
    }

    public function getHeading(): string
    {
        return 'متابعة الطلبات';
    }

    public static function getNavigationBadge(): ?string
    {
        if (! auth()->check()) {
            return null;
        }

        $count = Order::listQueryForAuthenticatedUser()
            ->staleWithoutLogs()
            ->count();

        return (string) $count;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    /**
     * @return array{stale: int, old_handed: int}
     */
    #[Computed]
    public function tabCounts(): array
    {
        return [
            'stale' => Order::listQueryForAuthenticatedUser()
                ->staleWithoutLogs()
                ->count(),
            'old_handed' => Order::listQueryForAuthenticatedUser()
                ->handedWithHandedLogOlderThan(7)
                ->count(),
        ];
    }

    public function table(Table $table): Table
    {
        return OrderTableBuilder::configure($table, function (): Builder {
            return match ($this->activeTab) {
                'stale' => (function (): Builder {
                    $query = Order::listQueryForAuthenticatedUser();
                    $query->staleWithoutLogs();

                    return $query;
                })(),
                'old_handed' => (function (): Builder {
                    $query = Order::listQueryForAuthenticatedUser();
                    $query->handedWithHandedLogOlderThan(7);

                    return $query;
                })(),
                default => Order::listQueryForAuthenticatedUser()->whereRaw('0 = 1'),
            };
        });
    }

    public static function canAccess(): bool
    {
        return OrderResource::canViewAny();
    }
}
