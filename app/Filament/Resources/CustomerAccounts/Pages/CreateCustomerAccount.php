<?php

namespace App\Filament\Resources\CustomerAccounts\Pages;

use App\Filament\Resources\CustomerAccounts\CustomerAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerAccount extends CreateRecord
{
    protected static string $resource = CustomerAccountResource::class;
}
