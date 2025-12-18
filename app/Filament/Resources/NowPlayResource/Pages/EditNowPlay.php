<?php

namespace App\Filament\Resources\NowPlayResource\Pages;

use App\Filament\Resources\NowPlayResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNowPlay extends EditRecord
{
    protected static string $resource = NowPlayResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
