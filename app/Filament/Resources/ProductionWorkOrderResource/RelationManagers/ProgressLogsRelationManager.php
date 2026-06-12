<?php

namespace App\Filament\Resources\ProductionWorkOrderResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Enums\ProgressStatus;

class ProgressLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'progressLogs';
    protected static ?string $title = 'Riwayat Progres';

    public function form(Schema $form): Schema
    {
        return $form->schema([
            // Log usually shouldn't be manually edited here
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->content(function () {
                $record = $this->getOwnerRecord();
                $record->unsetRelation('progressLogs'); // Force reload for real-time updates
                return view('filament.forms.components.work-order-progress-header', ['record' => $record]);
            })
            ->headerActions([
                \Filament\Actions\Action::make('log_progress')
                    ->label('Log Progres')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('info')
                    ->visible(fn () => auth()->user()?->hasAnyRole(['PRODUCTION', 'SUPER_ADMIN', 'MANAGER', 'DEVELOPER']))
                    ->form([
                        \Filament\Forms\Components\Select::make('stage_id')
                            ->label('Tahap Saat Ini')
                            ->options(\App\Models\ProductionStage::orderBy('sort_order')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn (\Filament\Resources\RelationManagers\RelationManager $livewire) => $livewire->getOwnerRecord()->current_stage_id),

                        \Filament\Forms\Components\Select::make('personnel_id')
                            ->label('Petugas')
                            ->options(\App\Models\Personnel::where('division', 'Production')->pluck('full_name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn (\Filament\Resources\RelationManagers\RelationManager $livewire) => $livewire->getOwnerRecord()->assigned_personnel_id),

                        \Filament\Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'STARTED' => 'Mulai',
                                'COMPLETED' => 'Selesai',
                            ])
                            ->required()
                            ->default(fn (\Filament\Resources\RelationManagers\RelationManager $livewire) => $livewire->getOwnerRecord()->status),

                        \Filament\Forms\Components\Textarea::make('note')
                            ->label('Catatan')
                            ->rows(2),

                        \Filament\Forms\Components\Placeholder::make('started_at_display')
                            ->label('Mulai Pada')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div x-data="{
                                    ts: ' . (now()->timestamp * 1000) . ',
                                    time: \'\',
                                    format() {
                                        let d = new Date(this.ts);
                                        let months = [\'Januari\',\'Februari\',\'Maret\',\'April\',\'Mei\',\'Juni\',\'Juli\',\'Agustus\',\'September\',\'Oktober\',\'November\',\'Desember\'];
                                        let day = String(d.getDate()).padStart(2, \'0\');
                                        let month = months[d.getMonth()];
                                        let year = d.getFullYear();
                                        let hour = String(d.getHours()).padStart(2, \'0\');
                                        let min = String(d.getMinutes()).padStart(2, \'0\');
                                        let sec = String(d.getSeconds()).padStart(2, \'0\');
                                        return day + \' \' + month + \' \' + year + \', \' + hour + \':\' + min + \':\' + sec + \' WIB\';
                                    }
                                }" x-init="time = format(); setInterval(() => { ts += 1000; time = format(); }, 1000)">
                                    <span x-text="time" class="text-sm font-medium"></span>
                                </div>
                            ')),
                    ])
                    ->action(function (\Filament\Resources\RelationManagers\RelationManager $livewire, array $data) {
                        try {
                            $record = $livewire->getOwnerRecord();
                            // Force realtime
                            $data['started_at'] = now();
                            
                            app(\App\Services\ProductionService::class)->addProgressLog(
                                $record->id,
                                $data,
                                auth()->id()
                            );
                            \Filament\Notifications\Notification::make()->title('Progres berhasil dicatat')->success()->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }
}
