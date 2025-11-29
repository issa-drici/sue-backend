<?php

namespace App\Repositories\SportSession;

use App\Entities\SportSession;
use App\Entities\User;
use App\Models\SportSessionModel;
use App\Models\SportSessionParticipantModel;
use App\Models\SportSessionCommentModel;
use App\Models\UserModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class SportSessionRepository implements SportSessionRepositoryInterface
{
    public function findById(string $id): ?SportSession
    {
        $model = SportSessionModel::with(['organizer', 'participants.user', 'comments.user'])->find($id);

        if (!$model) {
            return null;
        }

        return $this->mapToEntity($model);
    }

    public function findAll(array $filters = [], int $page = 1, int $limit = 20): LengthAwarePaginator
    {
        $query = SportSessionModel::with(['organizer', 'participants.user', 'comments.user'])
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

        $paginator = $query->orderBy('date', 'asc')->paginate($limit, ['*'], 'page', $page);

        $paginator->getCollection()->transform(function ($model) {
            return $this->mapToEntity($model);
        });

        return $paginator;
    }

    public function findMySessions(string $userId, array $filters = [], int $page = 1, int $limit = 20): LengthAwarePaginator
    {
        $query = SportSessionModel::with(['organizer', 'participants.user', 'comments.user'])
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
                $q->where('date', '<', now()->format('Y-m-d'))
                  ->orWhere('status', 'cancelled');
            });
            // Pour l'historique : tri par date décroissante puis heure décroissante (plus récent en premier)
            $paginator = $query->orderBy('date', 'desc')->orderBy('start_time', 'desc')->paginate($limit, ['*'], 'page', $page);
        } else {
            // Pour les sessions futures/actuelles : exclure les sessions passées et les sessions annulées
            $query->where('status', 'active')
                  ->where('date', '>=', now()->format('Y-m-d'));
            $paginator = $query->orderBy('date', 'asc')->orderBy('start_time', 'asc')->paginate($limit, ['*'], 'page', $page);
        }

        $paginator->getCollection()->transform(function ($model) {
            return $this->mapToEntity($model);
        });

        return $paginator;
    }

    public function create(array $data): SportSession
    {
        $model = SportSessionModel::create([
            'id' => Str::uuid(),
            'sport' => $data['sport'],
            'date' => $data['date'],
            'start_time' => $data['startTime'],
            'end_time' => $data['endTime'],
            'location' => $data['location'],
            'max_participants' => $data['maxParticipants'] ?? null,
            'price_per_person' => $data['pricePerPerson'] ?? null,
            'organizer_id' => $data['organizer_id'],
            'status' => 'active',
        ]);

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
        if (isset($data['startTime'])) {
            $mappedData['start_time'] = $data['startTime'];
        }
        if (isset($data['endTime'])) {
            $mappedData['end_time'] = $data['endTime'];
        }
        if (array_key_exists('maxParticipants', $data)) {
            $mappedData['max_participants'] = $data['maxParticipants'];
        }
        if (array_key_exists('pricePerPerson', $data)) {
            $mappedData['price_per_person'] = $data['pricePerPerson'];
        }
        if (isset($data['date'])) {
            $mappedData['date'] = $data['date'];
        }
        if (isset($data['location'])) {
            $mappedData['location'] = $data['location'];
        }
        if (isset($data['status'])) {
            $mappedData['status'] = $data['status'];
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
        $query = SportSessionModel::with(['organizer', 'participants.user', 'comments.user'])
            ->where('status', 'active') // Exclure les sessions annulées
            ->byOrganizer($organizerId);

        if (isset($filters['sport'])) {
            $query->bySport($filters['sport']);
        }

        return $query->orderBy('date', 'asc')->get()->map(function ($model) {
            return $this->mapToEntity($model);
        })->toArray();
    }

    public function findByParticipant(string $userId, array $filters = []): array
    {
        $query = SportSessionModel::with(['organizer', 'participants.user', 'comments.user'])
            ->where('status', 'active') // Exclure les sessions annulées
            ->whereHas('participants', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });

        if (isset($filters['sport'])) {
            $query->bySport($filters['sport']);
        }

        return $query->orderBy('date', 'asc')->get()->map(function ($model) {
            return $this->mapToEntity($model);
        })->toArray();
    }

    public function findByParticipantPaginated(string $userId, array $filters = [], int $page = 1, int $limit = 20): LengthAwarePaginator
    {
        $query = SportSessionModel::with(['organizer', 'participants.user', 'comments.user'])
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

        $paginator = $query->orderBy('date', 'asc')->orderBy('start_time', 'asc')->paginate($limit, ['*'], 'page', $page);

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

        // Récupérer les participants existants et les trier par statut
        $participants = $model->participants->map(function ($participant) {
            return [
                'id' => $participant->user->id,
                'fullName' => $participant->user->firstname . ' ' . $participant->user->lastname,
                'status' => $participant->status,
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
            // Ajouter l'organisateur au début de la liste (statut accepted)
            array_unshift($participants, [
                'id' => $model->organizer->id,
                'fullName' => $model->organizer->firstname . ' ' . $model->organizer->lastname,
                'status' => 'accepted', // L'organisateur est automatiquement accepté
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
            $model->date->format('Y-m-d'),
            $model->start_time,
            $model->end_time,
            $model->location,
            $model->max_participants,
            $model->price_per_person,
            $model->status ?? 'active',
            $organizer,
            $participants,
            $comments
        );
    }

    /**
     * Trouve les sessions actives qui commencent à une date et heure précises
     * Optimisé pour les rappels - ne charge que les sessions nécessaires
     * Compatible PostgreSQL et MySQL
     *
     * @param string $date Date au format Y-m-d
     * @param string $time Heure au format H:i
     * @param int $marginMinutes Marge de tolérance en minutes (par défaut 1 minute)
     */
    public function findByDateAndTime(string $date, string $time, int $marginMinutes = 1): array
    {
        // Formater l'heure pour enlever les secondes si présentes
        $timeFormatted = preg_replace('/:(\d{2})$/', '', $time);

        // Vérifier que le format est valide
        if (empty($timeFormatted) || !preg_match('/^\d{2}:\d{2}$/', $timeFormatted)) {
            return [];
        }

        // Détecter le driver de base de données
        $connection = \Illuminate\Support\Facades\DB::connection();
        $driver = $connection->getDriverName();

        $models = SportSessionModel::with(['organizer', 'participants.user'])
            ->where('status', 'active')
            ->whereDate('date', $date);

        // Utiliser la syntaxe appropriée selon le driver
        if ($driver === 'pgsql') {
            // PostgreSQL : utiliser TO_CHAR pour formater l'heure en HH24:MI
            // start_time est un type TIME(0), on le formate directement
            if ($marginMinutes > 0) {
                // Créer une plage de temps en utilisant une date complète pour Carbon
                $timeObj = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $date . ' ' . $timeFormatted . ':00');
                if (!$timeObj) {
                    return [];
                }

                $minTime = $timeObj->copy()->subMinutes($marginMinutes)->format('H:i');
                $maxTime = $timeObj->copy()->addMinutes($marginMinutes)->format('H:i');

                // Gérer le cas où on passe minuit (ex: 23:59 > 00:01)
                if ($minTime > $maxTime) {
                    // Si minTime > maxTime, on cherche dans deux plages : de minTime à 23:59 et de 00:00 à maxTime
                    $models->where(function ($query) use ($minTime, $maxTime) {
                        $query->whereRaw("TO_CHAR(start_time, 'HH24:MI') >= ?", [$minTime])
                              ->orWhereRaw("TO_CHAR(start_time, 'HH24:MI') <= ?", [$maxTime]);
                    });
                } else {
                    $models->whereRaw("TO_CHAR(start_time, 'HH24:MI') >= ? AND TO_CHAR(start_time, 'HH24:MI') <= ?", [$minTime, $maxTime]);
                }
            } else {
                // Recherche exacte
                $models->whereRaw("TO_CHAR(start_time, 'HH24:MI') = ?", [$timeFormatted]);
            }
        } else {
            // MySQL/MariaDB : utiliser TIME_FORMAT
            if ($marginMinutes > 0) {
                try {
                    // Créer une plage de temps en utilisant une date complète pour Carbon
                    $timeObj = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $date . ' ' . $timeFormatted . ':00');
                    if (!$timeObj) {
                        return [];
                    }

                    $minTime = $timeObj->copy()->subMinutes($marginMinutes)->format('H:i');
                    $maxTime = $timeObj->copy()->addMinutes($marginMinutes)->format('H:i');

                    // Gérer le cas où on passe minuit (ex: 23:59 > 00:01)
                    if ($minTime > $maxTime) {
                        // Si minTime > maxTime, on cherche dans deux plages : de minTime à 23:59 et de 00:00 à maxTime
                        $models->where(function ($query) use ($minTime, $maxTime) {
                            $query->whereRaw('TIME_FORMAT(start_time, "%H:%i") >= ?', [$minTime])
                                  ->orWhereRaw('TIME_FORMAT(start_time, "%H:%i") <= ?', [$maxTime]);
                        });
                    } else {
                        $models->whereRaw('TIME_FORMAT(start_time, "%H:%i") >= ? AND TIME_FORMAT(start_time, "%H:%i") <= ?', [$minTime, $maxTime]);
                    }
                } catch (\Exception $e) {
                    // En cas d'erreur, fallback sur une recherche exacte
                    $models->whereRaw('TIME_FORMAT(start_time, "%H:%i") = ?', [$timeFormatted]);
                }
            } else {
                $models->whereRaw('TIME_FORMAT(start_time, "%H:%i") = ?', [$timeFormatted]);
            }
        }

        $models = $models->get();

        return $models->map(function ($model) {
            return $this->mapToEntity($model);
        })->toArray();
    }
}
