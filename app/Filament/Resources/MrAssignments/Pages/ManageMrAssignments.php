<?php

namespace App\Filament\Resources\MrAssignments\Pages;

use App\Filament\Resources\MrAssignments\MrAssignmentResource;
use App\Models\MrAssignment;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageMrAssignments extends ManageRecords
{
    protected static string $resource = MrAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Assign to MR')
                ->using(function (array $data): MrAssignment {
                    $assignment = MrAssignment::create(
                        MrAssignmentResource::payload($data, auth()->id())
                    );

                    Notification::make()
                        ->title("Assigned to {$assignment->mrUser?->name}")
                        ->success()
                        ->send();

                    return $assignment;
                }),
        ];
    }
}
