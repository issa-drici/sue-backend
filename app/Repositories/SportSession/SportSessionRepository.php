<?php

namespace App\Repositories\SportSession;

use App\Entities\SportSession;
use App\Entities\User;
use App\Models\SportSessionModel;
use App\Models\SportSessionParticipantModel;
use App\Models\SportSessionCommentModel;
use App\Models\UserModel;
use App\Models\SessionSlotProposalModel;
use App\Models\SessionSlotAvailabilityModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class SportSessionRepository implements SportSessionRepositoryInterface
{
    public function findById(string $id): ?SportSession
    {
        $model = SportSessionModel::with(['organizer.profile.avatarFile', 'participants.user.profile.avatarFile', 'comments.user', 'slotProposals.availabilities'])->find($id);

        if (!$model) {
            return null;
        }

        return $this->mapToEntity($model);
    }

    public function findByShareToken(string $shareToken): ?SportSession
    {
        $model = SportSessionModel::with(['organizer.profile.avatarFile', 'participants.user.profile.avatarFile', 'comments.user', 'slotProposals.availabilities'])
            ->where('share_token', $shareToken)
            ->first();

        if (!$model) {
            return null;
        }

        return $this->mapToEntity($model);
    }

    public function findAll(array $filters = [], int $page = 1, int $limit = 20): LengthAwarePaginator
    {
        $query = SportSessionModel::with(['organizer.profile.avatarFile', 'participants.user.profile.avatarFile', 'comments.user', 'slotProposals.availabilities'])
            ->where('status', 'active'); // Exclure les sessions annulées

        if (isset($filters['sport'])) {
            $query->bySport($filters['sport']);
        }

        if (isset($filters['date'])) {
            $query->byDate($filters['date']);
        }

        if (isset($filters['organizer_id'])) {
            $query->byOrganizer($filters['organizer_id']);
        }

        $paginator = $query->orderBy('start_date', 'asc')->paginate($limit, ['*'], 'page', $page);

        $paginator->getCollection()->transform(function ($model) {
            return $this->mapToEntity($model);
        });

        return $paginator;
    }

    public function findMySessions(string $userId, array $filters = [], int $page = 1, int $limit = 20): LengthAwarePaginator
    {
        $query = SportSessionModel::with(['organizer.profile.avatarFile', 'participants.user.profile.avatarFile', 'comments.user', 'slotProposals.availabilities'])
            ->whereHas('participants', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->whereNotIn('status', ['declined']); // Exclure complètement les sessions refusées
            });

        if (isset($filters['sport'])) {
            $query->bySport($filters['sport']);
        }

        if (isset($filters['date'])) {
            $query->byDate($filters['date']);
        }

        // Filtre pour les sessions passées (historique)
        if (isset($filters['past_sessions']) && $filters['past_sessions']) {
            // Pour l'historique : inclure les sessions passées ET les sessions annulées (même futures)
            $query->where(function ($q) {
                $q->where('end_date', '<', now())
                  ->orWhere('status', 'cancelled');
            });
            // Pour l'historique : tri par date décroissante (plus récent en premier)
            $paginator = $query->orderBy('end_date', 'desc')->paginate($limit, ['*'], 'page', $page);
        } else {
            // Pour les sessions futures/actuelles : exclure les sessions passées et les sessions annulées
            $query->where('status', 'active')
                  ->where('end_date', '>=', now());
            $paginator = $query->orderBy('start_date', 'asc')->paginate($limit, ['*'], 'page', $page);
        }

        $paginator->getCollection()->transform(function ($model) {
            return $this->mapToEntity($model);
        });

        return $paginator;
    }

    public function create(array $data): SportSession
    {
        $scheduling = $data['scheduling'] ?? 'scheduled';

        if ($scheduling === 'finding_slot') {
            // Créneau à définir : start/end couvrent la plage des créneaux proposés
            $slots = array_map(fn ($slot) => [
                'start_date' => $this->parseParisDateTime($slot['date'], $slot['startTime']),
                'end_date' => $this->parseParisEndDateTime($slot['date'], $slot['startTime'], $slot['endTime']),
            ], $data['slotProposals']);
            $startDate = min(array_column($slots, 'start_date'));
            $endDate = max(array_column($slots, 'end_date'));
        } else {
            // Les dates/heures fournies sont en heure locale (Europe/Paris)
            // On les crée en Europe/Paris puis on les convertit en UTC pour le stockage
            $startDate = $this->parseParisDateTime($data['date'], $data['startTime']);
            $endDate = $this->parseParisEndDateTime($data['date'], $data['startTime'], $data['endTime']);
        }

        $model = SportSessionModel::create([
            'id' => Str::uuid(),
            'sport' => $data['sport'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'location' => $data['location'],
            'max_participants' => $data['maxParticipants'] ?? null,
            'price_per_person' => $data['pricePerPerson'] ?? null,
            'organizer_id' => $data['organizer_id'],
            'status' => 'active',
            'scheduling' => $scheduling,
            'share_token' => $this->generateUniqueShareToken(),
        ]);

        if ($scheduling === 'finding_slot') {
            foreach ($slots as $slot) {
                $model->slotProposals()->create(['id' => Str::uuid()] + $slot);
            }
        }

        // Ajouter automatiquement l'organisateur comme participant
        $this->addParticipant($model->id, $data['organizer_id'], 'accepted');

        return $this->findById($model->id);
    }

    public function update(string $id, array $data): ?SportSession
    {
        $model = SportSessionModel::find($id);

        if (!$model) {
            return null;
        }

        // Mapper les champs de l'API vers les champs de la base de données
        $mappedData = [];
        if (isset($data['sport'])) {
            $mappedData['sport'] = $data['sport'];
        }
        // Si date, startTime ou endTime sont fournis, combiner en start_date et end_date
        // Les dates/heures fournies sont en heure locale (Europe/Paris), on les convertit en UTC
        if (isset($data['date']) && isset($data['startTime'])) {
            $mappedData['start_date'] = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $data['date'] . ' ' . $data['startTime'] . ':00', 'Europe/Paris')->utc();
        }
        if (isset($data['date']) && isset($data['endTime'])) {
            // Fin avant le début : la session se termine le lendemain (ex. 23:00 → 00:30)
            $startTime = $data['startTime'] ?? $model->start_date->copy()->timezone('Europe/Paris')->format('H:i');
            $mappedData['end_date'] = $this->parseParisEndDateTime($data['date'], $startTime, $data['endTime']);
        }
        if (array_key_exists('maxParticipants', $data)) {
            $mappedData['max_participants'] = $data['maxParticipants'];
        }
        if (array_key_exists('pricePerPerson', $data)) {
            $mappedData['price_per_person'] = $data['pricePerPerson'];
        }
        if (isset($data['location'])) {
            $mappedData['location'] = $data['location'];
        }
        if (isset($data['status'])) {
            $mappedData['status'] = $data['status'];
        }
        if (isset($data['organizer_id'])) {
            $mappedData['organizer_id'] = $data['organizer_id'];
        }

        $model->update($mappedData);

        return $this->findById($id);
    }

    public function delete(string $id): bool
    {
        $model = SportSessionModel::find($id);

        if (!$model) {
            return false;
        }

        return $model->delete();
    }

    public function findByOrganizer(string $organizerId, array $filters = []): array
    {
        $query = SportSessionModel::with(['organizer.profile.avatarFile', 'participants.user.profile.avatarFile', 'comments.user', 'slotProposals.availabilities'])
            ->where('status', 'active') // Exclure les sessions annulées
            ->byOrganizer($organizerId);

        if (isset($filters['sport'])) {
            $query->bySport($filters['sport']);
        }

        return $query->orderBy('start_date', 'asc')->get()->map(function ($model) {
            return $this->mapToEntity($model);
        })->toArray();
    }

    public function findByParticipant(string $userId, array $filters = []): array
    {
        $query = SportSessionModel::with(['organizer.profile.avatarFile', 'participants.user.profile.avatarFile', 'comments.user', 'slotProposals.availabilities'])
            ->where('status', 'active') // Exclure les sessions annulées
            ->whereHas('participants', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });

        if (isset($filters['sport'])) {
            $query->bySport($filters['sport']);
        }

        return $query->orderBy('start_date', 'asc')->get()->map(function ($model) {
            return $this->mapToEntity($model);
        })->toArray();
    }

    public function findByParticipantPaginated(string $userId, array $filters = [], int $page = 1, int $limit = 20): LengthAwarePaginator
    {
        $query = SportSessionModel::with(['organizer.profile.avatarFile', 'participants.user.profile.avatarFile', 'comments.user', 'slotProposals.availabilities'])
            ->where('status', 'active') // Exclure les sessions annulées
            ->whereHas('participants', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->whereNotIn('status', ['declined']); // Exclure complètement les sessions refusées
            });

        if (isset($filters['sport'])) {
            $query->bySport($filters['sport']);
        }

        if (isset($filters['date'])) {
            $query->byDate($filters['date']);
        }

        $paginator = $query->orderBy('start_date', 'asc')->paginate($limit, ['*'], 'page', $page);

        $paginator->getCollection()->transform(function ($model) {
            return $this->mapToEntity($model);
        });

        return $paginator;
    }

    public function addParticipant(string $sessionId, string $userId, string $status = 'pending'): bool
    {
        return SportSessionParticipantModel::create([
            'id' => Str::uuid(),
            'session_id' => $sessionId,
            'user_id' => $userId,
            'status' => $status,
        ]) !== null;
    }

    public function updateParticipantStatus(string $sessionId, string $userId, string $status): bool
    {
        $participant = SportSessionParticipantModel::where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->first();

        if (!$participant) {
            return false;
        }

        return $participant->update(['status' => $status]);
    }

    public function removeParticipant(string $sessionId, string $userId): bool
    {
        $participant = SportSessionParticipantModel::where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->first();

        if (!$participant) {
            return false;
        }

        return $participant->delete();
    }

    public function addComment(string $sessionId, string $userId, string $content): bool
    {
        return SportSessionCommentModel::create([
            'id' => Str::uuid(),
            'session_id' => $sessionId,
            'user_id' => $userId,
            'content' => $content,
        ]) !== null;
    }

    public function getComments(string $sessionId): array
    {
        return SportSessionCommentModel::with('user')
            ->bySession($sessionId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'userId' => $comment->user_id,
                    'fullName' => $comment->user->firstname . ' ' . $comment->user->lastname,
                    'content' => $comment->content,
                    'createdAt' => $comment->created_at->format('c'),
                ];
            })
            ->toArray();
    }

    public function isUserInvited(string $sessionId, string $userId): bool
    {
        return SportSessionParticipantModel::where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->exists();
    }

    public function isUserParticipant(string $sessionId, string $userId): bool
    {
        return SportSessionParticipantModel::where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->whereIn('status', ['accepted', 'confirmed'])
            ->exists();
    }

    public function findParticipant(string $sessionId, string $userId): ?array
    {
        $participant = SportSessionParticipantModel::with('user')
            ->where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->first();

        if (!$participant) {
            return null;
        }

        return [
            'id' => $participant->id,
            'session_id' => $participant->session_id,
            'user_id' => $participant->user_id,
            'status' => $participant->status,
            'user' => [
                'id' => $participant->user->id,
                'firstname' => $participant->user->firstname,
                'lastname' => $participant->user->lastname,
                'email' => $participant->user->email
            ]
        ];
    }

    public function inviteUser(string $sessionId, string $userId): bool
    {
        return $this->addParticipant($sessionId, $userId, 'pending');
    }

    /**
     * Génère un token de partage opaque, unique et non énumérable.
     */
    private function generateUniqueShareToken(): string
    {
        do {
            $token = Str::random(40);
        } while (SportSessionModel::where('share_token', $token)->exists());

        return $token;
    }

    private function mapToEntity(SportSessionModel $model): SportSession
    {
        $organizer = new User(
            $model->organizer->id,
            $model->organizer->firstname,
            $model->organizer->lastname,
            $model->organizer->email,
            $model->organizer->phone,
            $model->organizer->role
        );

        // "Trouver un créneau" : date du dernier créneau ajouté, pour repérer les invités
        // qui ont répondu avant (ils doivent revoir leurs dispos)
        $latestSlotAddedAt = ($model->scheduling ?? 'scheduled') === 'finding_slot'
            ? $model->slotProposals->max('created_at')
            : null;

        // Récupérer les participants existants et les trier par statut
        $participants = $model->participants->map(function ($participant) use ($latestSlotAddedAt) {
            $profile = $participant->user->profile;
            $avatarUrl = $profile && $profile->avatarFile ? $profile->avatarFile->url : null;

            return [
                'id' => $participant->user->id,
                'fullName' => $participant->user->firstname . ' ' . $participant->user->lastname,
                'status' => $participant->status,
                'avatarUrl' => $avatarUrl,
                'hasGivenAvailability' => $participant->slot_responded_at !== null,
                'hasNewSlots' => $participant->slot_responded_at !== null
                    && $latestSlotAddedAt !== null
                    && $participant->slot_responded_at->lte($latestSlotAddedAt),
                'created_at' => $participant->created_at, // Pour le tri secondaire
            ];
        })->sortBy(function ($participant) {
            // Tri par statut : accepted (1), pending (2), declined (3)
            $statusOrder = [
                'accepted' => 1,
                'pending' => 2,
                'declined' => 3
            ];
            return $statusOrder[$participant['status']] ?? 4;
        })->sortBy('created_at') // Tri secondaire par date d'ajout
        ->map(function ($participant) {
            // Retirer le champ created_at du résultat final
            unset($participant['created_at']);
            return $participant;
        })->values()->toArray();

        // Ajouter l'organisateur comme participant s'il n'est pas déjà dans la liste
        $organizerAlreadyInParticipants = collect($participants)->contains('id', $model->organizer->id);

        if (!$organizerAlreadyInParticipants) {
            $orgProfile = $model->organizer->profile;
            $orgAvatarUrl = $orgProfile && $orgProfile->avatarFile ? $orgProfile->avatarFile->url : null;

            // Ajouter l'organisateur au début de la liste (statut accepted)
            array_unshift($participants, [
                'id' => $model->organizer->id,
                'fullName' => $model->organizer->firstname . ' ' . $model->organizer->lastname,
                'status' => 'accepted', // L'organisateur est automatiquement accepté
                'avatarUrl' => $orgAvatarUrl,
            ]);
        }

        $comments = $model->comments->map(function ($comment) {
            return [
                'id' => $comment->id,
                'userId' => $comment->user->id,
                'fullName' => $comment->user->firstname . ' ' . $comment->user->lastname,
                'content' => $comment->content,
                'createdAt' => $comment->created_at->format('c'),
            ];
        })->toArray();

        return new SportSession(
            $model->id,
            $model->sport,
            $model->start_date,
            $model->end_date,
            $model->location,
            $model->max_participants,
            $model->price_per_person,
            $model->status ?? 'active',
            $organizer,
            $participants,
            $comments,
            $model->share_token,
            $model->scheduling ?? 'scheduled',
            $this->mapSlotProposals($model)
        );
    }

    public function setSlotAvailabilities(string $sessionId, string $userId, array $slotIds): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($sessionId, $userId, $slotIds) {
            $sessionSlotIds = SessionSlotProposalModel::where('session_id', $sessionId)->pluck('id');

            SessionSlotAvailabilityModel::whereIn('slot_id', $sessionSlotIds)
                ->where('user_id', $userId)
                ->delete();

            foreach (array_unique($slotIds) as $slotId) {
                SessionSlotAvailabilityModel::create([
                    'id' => Str::uuid(),
                    'slot_id' => $slotId,
                    'user_id' => $userId,
                ]);
            }

            SportSessionParticipantModel::where('session_id', $sessionId)
                ->where('user_id', $userId)
                ->update(['slot_responded_at' => now()]);
        });
    }

    public function updateSlotProposals(string $sessionId, array $removedSlotIds, array $newSlots): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($sessionId, $removedSlotIds, $newSlots) {
            SessionSlotProposalModel::where('session_id', $sessionId)->whereIn('id', $removedSlotIds)->delete();

            foreach ($newSlots as $slot) {
                SessionSlotProposalModel::create([
                    'id' => Str::uuid(),
                    'session_id' => $sessionId,
                    'start_date' => $this->parseParisDateTime($slot['date'], $slot['startTime']),
                    'end_date' => $this->parseParisEndDateTime($slot['date'], $slot['startTime'], $slot['endTime']),
                ]);
            }

            $upcoming = SessionSlotProposalModel::where('session_id', $sessionId)->where('start_date', '>', now());
            $range = [
                'start_date' => (clone $upcoming)->min('start_date'),
                'end_date' => (clone $upcoming)->max('end_date'),
            ];

            SportSessionModel::where('id', $sessionId)->update(array_filter($range) + [
                // De nouveaux créneaux : l'organisateur pourra être prévenu à nouveau s'ils passent tous
                'slot_expired_notified_at' => null,
            ]);
        });
    }

    public function findSessionsAwaitingSlot(): array
    {
        return SportSessionModel::with(['organizer.profile.avatarFile', 'participants.user.profile.avatarFile', 'slotProposals.availabilities'])
            ->where('status', 'active')
            ->where('scheduling', 'finding_slot')
            ->get()
            ->map(fn ($model) => [
                'session' => $this->mapToEntity($model),
                'createdAt' => $model->created_at,
                'reminderSentAt' => $model->slot_reminder_sent_at,
                'expiredNotifiedAt' => $model->slot_expired_notified_at,
            ])
            ->all();
    }

    public function markSlotNotificationSent(string $sessionId, string $column): void
    {
        if (!in_array($column, ['slot_reminder_sent_at', 'slot_expired_notified_at'], true)) {
            throw new \InvalidArgumentException("Colonne inconnue : {$column}");
        }
        SportSessionModel::where('id', $sessionId)->update([$column => now()]);
    }

    public function confirmSlot(string $sessionId, string $slotId): void
    {
        $slot = SessionSlotProposalModel::where('session_id', $sessionId)->findOrFail($slotId);

        SportSessionModel::where('id', $sessionId)->update([
            'start_date' => $slot->start_date,
            'end_date' => $slot->end_date,
            'scheduling' => 'scheduled',
        ]);
    }

    private function mapSlotProposals(SportSessionModel $model): array
    {
        if (($model->scheduling ?? 'scheduled') !== 'finding_slot') {
            return [];
        }

        // Un créneau déjà commencé ne peut plus être choisi : on ne garde que ceux à venir
        return $model->slotProposals->filter(fn ($slot) => $slot->start_date->isFuture())->map(function ($slot) {
            $start = $slot->start_date->copy()->setTimezone('Europe/Paris');
            $end = $slot->end_date->copy()->setTimezone('Europe/Paris');
            $availableUserIds = $slot->availabilities->pluck('user_id')->values()->toArray();

            return [
                'id' => $slot->id,
                'date' => $start->format('Y-m-d'),
                'startTime' => $start->format('H:i'),
                'endTime' => $end->format('H:i'),
                'startDate' => $slot->start_date->format('c'),
                'endDate' => $slot->end_date->format('c'),
                'availableUserIds' => $availableUserIds,
                'availabilityCount' => count($availableUserIds),
            ];
        })->values()->toArray();
    }

    private function parseParisDateTime(string $date, string $time): \Carbon\Carbon
    {
        return \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $date . ' ' . $time . ':00', 'Europe/Paris')->utc();
    }

    /**
     * Heure de fin d'un créneau : une fin avant le début tombe le lendemain (ex. 23:00 → 00:30)
     */
    private function parseParisEndDateTime(string $date, string $startTime, string $endTime): \Carbon\Carbon
    {
        // Le jour est ajouté en heure de Paris : en UTC, le changement d'heure décalerait la fin d'une heure
        $endDate = $endTime <= $startTime ? \Carbon\Carbon::parse($date)->addDay()->format('Y-m-d') : $date;
        return $this->parseParisDateTime($endDate, $endTime);
    }

    /**
     * Trouve les sessions actives qui commencent à une date et heure précises
     * Optimisé pour les rappels - ne charge que les sessions nécessaires
     *
     * @param string $date Date au format Y-m-d
     * @param string $time Heure au format H:i
     * @param int $marginMinutes Marge de tolérance en minutes (par défaut 1 minute)
     */
    public function findByDateAndTime(string $date, string $time, int $marginMinutes = 1): array
    {
        try {
            // Parser la date
            $dateParts = explode('-', $date);
            if (count($dateParts) !== 3) {
                return [];
            }

            // Parser l'heure (gère H:i et H:i:s)
            $timeParts = explode(':', $time);
            if (count($timeParts) < 2) {
                return [];
            }

            $hour = (int) $timeParts[0];
            $minute = (int) $timeParts[1];
            $second = isset($timeParts[2]) ? (int) $timeParts[2] : 0;

            // Créer la date/heure cible directement en Europe/Paris
            $targetDateTime = \Carbon\Carbon::create(
                (int) $dateParts[0], // year
                (int) $dateParts[1], // month
                (int) $dateParts[2], // day
                $hour,
                $minute,
                $second,
                'Europe/Paris'
            );

            if (!$targetDateTime) {
                return [];
            }

            // Convertir en UTC pour la comparaison avec la base de données (qui stocke en UTC)
            $targetDateTime = $targetDateTime->utc();
        } catch (\Exception $e) {
            return [];
        }

        $models = SportSessionModel::with(['organizer.profile.avatarFile', 'participants.user.profile.avatarFile'])
            ->where('status', 'active')
            ->where('scheduling', 'scheduled'); // Pas de rappel tant que le créneau n'est pas choisi

        if ($marginMinutes > 0) {
            $minDateTime = $targetDateTime->copy()->subMinutes($marginMinutes);
            $maxDateTime = $targetDateTime->copy()->addMinutes($marginMinutes);

            $models->whereBetween('start_date', [
                $minDateTime->toDateTimeString(),
                $maxDateTime->toDateTimeString()
            ]);
        } else {
            // Recherche exacte à la minute près - utiliser whereYear, whereMonth, whereDay, whereHour, whereMinute
            $models->whereYear('start_date', $targetDateTime->year)
                   ->whereMonth('start_date', $targetDateTime->month)
                   ->whereDay('start_date', $targetDateTime->day)
                   ->whereHour('start_date', $targetDateTime->hour)
                   ->whereMinute('start_date', $targetDateTime->minute);
        }

        $models = $models->get();

        return $models->map(function ($model) {
            return $this->mapToEntity($model);
        })->toArray();
    }
}
