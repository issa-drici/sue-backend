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
        $query = SportSessionModel::with(['organizer', 'participants.user', 'comments.user']);

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
            $query->where('date', '<', now()->format('Y-m-d'));
            // Pour l'historique : tri par date décroissante puis heure décroissante (plus récent en premier)
            $paginator = $query->orderBy('date', 'desc')->orderBy('time', 'desc')->paginate($limit, ['*'], 'page', $page);
        } else {
            // Pour les sessions futures/actuelles : exclure les sessions passées et trier par date croissante
            $query->where('date', '>=', now()->format('Y-m-d'));
            $paginator = $query->orderBy('date', 'asc')->orderBy('time', 'asc')->paginate($limit, ['*'], 'page', $page);
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
            'time' => $data['time'],
            'location' => $data['location'],
            'max_participants' => $data['maxParticipants'] ?? null,
            'organizer_id' => $data['organizer_id'],
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

        $model->update($data);

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

        $paginator = $query->orderBy('date', 'asc')->orderBy('time', 'asc')->paginate($limit, ['*'], 'page', $page);

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

        // Récupérer les participants existants
        $participants = $model->participants->map(function ($participant) {
            return [
                'id' => $participant->user->id,
                'fullName' => $participant->user->firstname . ' ' . $participant->user->lastname,
                'status' => $participant->status,
            ];
        })->toArray();

        // Ajouter l'organisateur comme participant s'il n'est pas déjà dans la liste
        $organizerAlreadyInParticipants = collect($participants)->contains('id', $model->organizer->id);

        if (!$organizerAlreadyInParticipants) {
            $participants[] = [
                'id' => $model->organizer->id,
                'fullName' => $model->organizer->firstname . ' ' . $model->organizer->lastname,
                'status' => 'accepted', // L'organisateur est automatiquement accepté
            ];
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
            $model->time,
            $model->location,
            $model->max_participants,
            $organizer,
            $participants,
            $comments
        );
    }
}
