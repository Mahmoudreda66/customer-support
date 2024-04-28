<?php

namespace App\Filament\Resources\ComplainResource\Pages;

use App\Filament\Resources\ComplainResource;
use App\Models\Complain;
use Exception;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateComplain extends CreateRecord
{
    protected static string $resource = ComplainResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['type'] = 'open';

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            DB::beginTransaction();

            $complain = Complain::query()->create([
                'user_id' => $data['user_id'],
                'customer_id' => $data['customer_id'],
                'machine_type_id' => $data['machine_type_id'],
                'machine_model_id' => $data['machine_model_id'],
                'type' => $data['type']
            ]);

            $complain->notes()->create([
                'user_id' => $data['user_id'],
                'complain' => $data['complain'],
                'solution' => $data['solution'],
                'type' => $data['type'],
            ]);

            DB::commit();

            return $complain;
        } catch (Exception) {
            DB::rollBack();

            abort(500);
        }
    }
}
