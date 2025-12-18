<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NowPlayResource\Pages;
use App\Models\NowPlay;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Filters\Filter;

use Carbon\Carbon;

class NowPlayResource extends Resource
{
    protected static ?string $model = NowPlay::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';
    protected static ?string $navigationGroup = 'Radio';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Optional: leave empty if read-only table
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('playout_id')->searchable(),
                Tables\Columns\TextColumn::make('artist')->searchable(),
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('duration'),
                Tables\Columns\TextColumn::make('start_time')->dateTime('d.m.Y H:i:s'),
            ])
            ->filters([
                Filter::make('playout_id')->query(fn($query, $value) => $query->where('playout_id', 'like', "%{$value}%")),
                Filter::make('artist')->query(fn($query, $value) => $query->where('artist', 'like', "%{$value}%")),
                Filter::make('title')->query(fn($query, $value) => $query->where('title', 'like', "%{$value}%")),
            ])
            ->defaultSort('created_at', 'desc');
    }


    public static function getRelations(): array
    {
        return [
            // Optional: relation managers here
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNowPlays::route('/'),
        ];
    }
}
