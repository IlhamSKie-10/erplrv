<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Informasi Akun')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('full_name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => \Illuminate\Support\Facades\Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255),
                        \Filament\Forms\Components\Select::make('status')
                            ->label('Status Akun')
                            ->options(\App\Enums\UserStatus::class)
                            ->default(\App\Enums\UserStatus::ACTIVE)
                            ->required(),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Hak Akses (Role)')
                    ->schema([
                        \Filament\Forms\Components\Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->required(),
                    ]),

                \Filament\Schemas\Components\Section::make('Profil Karyawan (Personnel)')
                    ->schema([
                        \Filament\Schemas\Components\Fieldset::make('personnel')
                            ->relationship('personnel')
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('code')
                                    ->label('Kode Pegawai (Otomatis)')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('Sistem akan generate otomatis berdasarkan divisi')
                                    ->maxLength(50),
                                \Filament\Forms\Components\Select::make('division')
                                    ->label('Divisi')
                                    ->options([
                                        'Design' => 'Design',
                                        'CS' => 'CS',
                                        'Production' => 'Production',
                                        'Management' => 'Management',
                                        'IT' => 'IT',
                                    ])
                                    ->live()
                                    ->required(),
                                \Filament\Forms\Components\Select::make('production_team')
                                    ->label('Tim Produksi')
                                    ->options([
                                        'Advertising 1' => 'Advertising 1',
                                        'Advertising 2' => 'Advertising 2',
                                        'Homedecor' => 'Homedecor',
                                    ])
                                    ->visible(fn ($get) => $get('division') === 'Production')
                                    ->required(fn ($get) => $get('division') === 'Production'),
                                \Filament\Forms\Components\Toggle::make('is_active')
                                    ->label('Status Karyawan Aktif')
                                    ->default(true),
                            ])->columns(2),
                    ]),
            ]);
    }
}
