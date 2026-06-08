<?php

namespace App\Filament\Resources\CustomerAccounts\Schemas;

use App\Enums\BusinessPriority;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CustomerAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                Select::make('business_priority')
                    ->options(BusinessPriority::class)
                    ->default('NORMAL')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
