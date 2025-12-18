<?php

namespace App\Filament\Resources\NowPlayResource\Pages;

use App\Filament\Resources\NowPlayResource;
use Filament\Resources\Pages\ListRecords;

class ListNowPlays extends ListRecords
{
    protected static string $resource = NowPlayResource::class;

    // Optional: disable create/edit/delete if you just want read-only
    protected function getActions(): array
    {
        return [];
    }
}
