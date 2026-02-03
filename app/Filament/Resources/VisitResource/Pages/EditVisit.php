<?php

namespace App\Filament\Resources\VisitResource\Pages;

use App\Filament\Resources\VisitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditVisit extends EditRecord
{
    protected static string $resource = VisitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label(__('app.actions.print_visit'))
                ->icon('heroicon-o-printer')
                ->url(fn () => route('visits.print', $this->record), true),
            Actions\Action::make('convertToHospitalization')
                ->label(__('app.actions.convert_to_hospitalization'))
                ->icon('heroicon-o-arrow-path')
                ->visible(function () {
                    $user = auth()->user();

                    return ($user?->isAdmin() || $user?->isStaff()) && $this->record->canBeConverted();
                })
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'type' => 'hospitalization',
                        'medical_info_complete' => false,
                        'updated_by' => Auth::id(),
                    ]);
                }),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['doctor_choice'] ?? null) !== 'other') {
            $data['doctor_name'] = $data['doctor_choice'] ?? $data['doctor_name'] ?? '';
        }

        unset($data['doctor_choice']);

        $data['type'] = 'visit';
        $data['status'] = 'active';
        $data['medical_info_complete'] = true;
        $data['updated_by'] = Auth::id();

        return $data;
    }
}
