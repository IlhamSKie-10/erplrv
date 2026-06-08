<?php

namespace App\Filament\Resources\CustomerAccounts;

use App\Filament\Resources\CustomerAccounts\Pages\CreateCustomerAccount;
use App\Filament\Resources\CustomerAccounts\Pages\EditCustomerAccount;
use App\Filament\Resources\CustomerAccounts\Pages\ListCustomerAccounts;
use App\Filament\Resources\CustomerAccounts\Schemas\CustomerAccountForm;
use App\Filament\Resources\CustomerAccounts\Tables\CustomerAccountsTable;
use App\Models\CustomerAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CustomerAccountResource extends Resource
{
    protected static ?string $model = CustomerAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CustomerAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerAccountsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\CustomerAccounts\RelationManagers\OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerAccounts::route('/'),
            'create' => CreateCustomerAccount::route('/create'),
            'edit' => EditCustomerAccount::route('/{record}/edit'),
        ];
    }
}
