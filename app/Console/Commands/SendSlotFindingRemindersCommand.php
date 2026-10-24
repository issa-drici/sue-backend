<?php

namespace App\Console\Commands;

use App\UseCases\SportSession\SendSlotFindingRemindersUseCase;
use Illuminate\Console\Command;

class SendSlotFindingRemindersCommand extends Command
{
    protected $signature = 'sessions:send-slot-reminders';

    protected $description = '"Trouver un créneau" : rappelle aux organisateurs de confirmer un créneau, et les prévient quand il n\'en reste plus';

    public function handle(SendSlotFindingRemindersUseCase $useCase): int
    {
        $result = $useCase->execute();
        $this->info("Rappels : {$result['reminders']}, sans créneau : {$result['expired']}");
        return self::SUCCESS;
    }
}
