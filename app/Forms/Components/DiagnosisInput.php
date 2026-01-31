<?php

namespace App\Forms\Components;

use App\Models\Diagnosis;
use Closure;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class DiagnosisInput extends Field
{
    protected string $view = 'forms.components.diagnosis-input';

    protected int|Closure $maxSuggestions = 8;

    protected int|Closure $minSearchLength = 2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hintAction(
            Action::make('addDiagnosis')
                ->label(__('app.labels.add_new_diagnosis'))
                ->icon('heroicon-o-plus')
                ->modalHeading(__('app.labels.add_new_diagnosis'))
                ->modalDescription(__('app.helpers.add_diagnosis_modal'))
                ->modalSubmitActionLabel(__('app.actions.save'))
                ->form(fn (): array => [
                    TextInput::make('name')
                        ->label(__('app.labels.diagnosis'))
                        ->required()
                        ->maxLength(255)
                        ->default($this->getState()),
                ])
                ->action(function (array $data, $component): void {
                    $diagnosis = Diagnosis::findOrCreateByName($data['name']);
                    $component->state($diagnosis->name);

                    Notification::make()
                        ->title(__('app.notifications.diagnosis_added'))
                        ->success()
                        ->send();
                })
        );
    }

    public function maxSuggestions(int|Closure $count): static
    {
        $this->maxSuggestions = $count;

        return $this;
    }

    public function minSearchLength(int|Closure $length): static
    {
        $this->minSearchLength = $length;

        return $this;
    }

    public function getMaxSuggestions(): int
    {
        return $this->evaluate($this->maxSuggestions);
    }

    public function getMinSearchLength(): int
    {
        return $this->evaluate($this->minSearchLength);
    }
}
