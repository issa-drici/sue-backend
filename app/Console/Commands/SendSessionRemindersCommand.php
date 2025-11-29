<?php

namespace App\Console\Commands;

use App\UseCases\SportSession\SendSessionRemindersUseCase;
use App\Repositories\SportSession\SportSessionRepositoryInterface;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendSessionRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:send-reminders {--test : Créer des sessions de test avant d\'envoyer les rappels}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envoie les rappels de session (24h avant, 1h avant, au démarrage)';

    /**
     * Execute the console command.
     */
    public function handle(
        SendSessionRemindersUseCase $useCase,
        SportSessionRepositoryInterface $sessionRepository
    ): int {
        // Mode test : créer des sessions de test
        if ($this->option('test')) {
            $this->info('Mode test activé - Création de sessions de test...');
            $this->createTestSessions($sessionRepository);
            $this->info('Sessions de test créées. Envoi des rappels...');
            $this->newLine();
        }

        $this->info('Début de l\'envoi des rappels de session...');

        try {
            $results = $useCase->execute();

            $this->info('Rappels 24h avant:');
            $this->line("  - Envoyés: {$results['reminder_24h']['sent']}");
            $this->line("  - Ignorés: {$results['reminder_24h']['skipped']}");
            if (!empty($results['reminder_24h']['errors'])) {
                $this->error("  - Erreurs: " . count($results['reminder_24h']['errors']));
                foreach ($results['reminder_24h']['errors'] as $error) {
                    $this->error("    * $error");
                }
            }

            $this->info('Rappels 1h avant:');
            $this->line("  - Envoyés: {$results['reminder_1h']['sent']}");
            $this->line("  - Ignorés: {$results['reminder_1h']['skipped']}");
            if (!empty($results['reminder_1h']['errors'])) {
                $this->error("  - Erreurs: " . count($results['reminder_1h']['errors']));
                foreach ($results['reminder_1h']['errors'] as $error) {
                    $this->error("    * $error");
                }
            }

            $this->info('Rappels au démarrage:');
            $this->line("  - Envoyés: {$results['reminder_start']['sent']}");
            $this->line("  - Ignorés: {$results['reminder_start']['skipped']}");
            if (!empty($results['reminder_start']['errors'])) {
                $this->error("  - Erreurs: " . count($results['reminder_start']['errors']));
                foreach ($results['reminder_start']['errors'] as $error) {
                    $this->error("    * $error");
                }
            }

            $this->info('Envoi des rappels terminé avec succès.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Erreur lors de l\'envoi des rappels: ' . $e->getMessage());
            $this->error($e->getTraceAsString());

            return Command::FAILURE;
        }
    }

    /**
     * Crée des sessions de test pour le débogage
     */
    private function createTestSessions(SportSessionRepositoryInterface $sessionRepository): void
    {
        $testUserIds = [
            '9f99f1f4-d3b0-4820-809a-84b204c1f446',
            '9f8fedb9-23a3-4294-bbd6-52813e86cbe9'
        ];

        $now = Carbon::now();

        // Session dans 24h
        $session24hDate = $now->copy()->addHours(24);
        $session24h = $sessionRepository->create([
            'sport' => 'tennis',
            'date' => $session24hDate->format('Y-m-d'),
            'startTime' => $session24hDate->format('H:i'),
            'endTime' => $session24hDate->copy()->addHours(2)->format('H:i'),
            'location' => 'Court de tennis de test - 24h',
            'organizer_id' => $testUserIds[0],
        ]);

        // Ajouter le deuxième utilisateur comme participant accepté
        $sessionRepository->addParticipant($session24h->getId(), $testUserIds[1], 'accepted');
        $this->line("✓ Session créée dans 24h : {$session24h->getId()} - Date: {$session24hDate->format('Y-m-d')} - Heure: {$session24hDate->format('H:i')}");

        // Session dans 1h
        $session1hDate = $now->copy()->addHour();
        $session1h = $sessionRepository->create([
            'sport' => 'football',
            'date' => $session1hDate->format('Y-m-d'),
            'startTime' => $session1hDate->format('H:i'),
            'endTime' => $session1hDate->copy()->addHours(2)->format('H:i'),
            'location' => 'Terrain de football de test - 1h',
            'organizer_id' => $testUserIds[0],
        ]);

        // Ajouter le deuxième utilisateur comme participant accepté
        $sessionRepository->addParticipant($session1h->getId(), $testUserIds[1], 'accepted');
        $this->line("✓ Session créée dans 1h : {$session1h->getId()} - Date: {$session1hDate->format('Y-m-d')} - Heure: {$session1hDate->format('H:i')}");

        // Session maintenant
        $sessionNowDate = $now->copy();
        $sessionNow = $sessionRepository->create([
            'sport' => 'basketball',
            'date' => $sessionNowDate->format('Y-m-d'),
            'startTime' => $sessionNowDate->format('H:i'),
            'endTime' => $sessionNowDate->copy()->addHours(2)->format('H:i'),
            'location' => 'Terrain de basketball de test - Maintenant',
            'organizer_id' => $testUserIds[0],
        ]);

        // Ajouter le deuxième utilisateur comme participant accepté
        $sessionRepository->addParticipant($sessionNow->getId(), $testUserIds[1], 'accepted');
        $this->line("✓ Session créée maintenant : {$sessionNow->getId()} - Date: {$sessionNowDate->format('Y-m-d')} - Heure: {$sessionNowDate->format('H:i')}");

        // Afficher les dates/heures recherchées pour le débogage
        $this->newLine();
        $this->info('Dates/heures qui seront recherchées:');
        $this->line("  24h: " . Carbon::now()->addHours(24)->format('Y-m-d H:i'));
        $this->line("  1h: " . Carbon::now()->addHour()->format('Y-m-d H:i'));
        $this->line("  Maintenant: " . Carbon::now()->format('Y-m-d H:i'));
    }
}

