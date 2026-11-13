<?php

namespace App\Services;

use App\Events\ChatMessageSent;
use App\Events\ChatReadUpdated;
use App\Events\ChatUserTyping;
use App\Exceptions\ChatException;
use App\Models\SessionChatMessageModel;
use App\Models\SessionChatStateModel;
use App\Models\SportSessionModel;
use App\Models\SportSessionParticipantModel;
use App\Repositories\PushToken\PushTokenRepositoryInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Chat d'une session.
 *
 * Accès : organisateur et tous les invités, y compris ceux qui ont refusé. Une personne retirée
 * de la session (plus de ligne participant) n'y a plus accès.
 *
 * Notifications d'un nouveau message :
 *  - mentionné (@) : toujours notifié (in-app + push), même s'il a refusé ou coupé le chat ;
 *  - sinon : push si l'invité n'a pas refusé et n'a pas coupé le chat de cette session.
 * Celui qui a le chat ouvert est filtré côté app (pas de bannière pour le chat affiché).
 *
 * Fiabilité : la base fait foi, le temps réel n'est qu'un accélérateur. L'app rattrape les
 * messages manqués avec `after` (ouverture, retour au premier plan, reconnexion) et chaque
 * message porte un clientId : un renvoi après coupure renvoie le message déjà créé.
 */
class SessionChatService
{
    public const MAX_LENGTH = 2000;
    private const PAGE_SIZE = 40;
    private const CATCH_UP_LIMIT = 200;
    private const PUSH_PREVIEW_LENGTH = 180;

    public function __construct(
        private SessionNotificationService $sessionNotificationService,
        private PushTokenRepositoryInterface $pushTokenRepository,
        private ExpoPushNotificationService $expoService
    ) {}

    /** Infos du chat pour l'utilisateur : membres (pour les @), sourdine, non-lus */
    public function meta(string $sessionId, string $userId): array
    {
        $this->assertMember($sessionId, $userId);
        $state = $this->state($sessionId, $userId);

        $unread = SessionChatMessageModel::where('session_id', $sessionId)
            ->where('id', '>', $state?->last_read_message_id ?? 0)
            ->where('user_id', '!=', $userId)
            ->count();

        return [
            'members' => $this->members($sessionId),
            'muted' => (bool) $state?->muted,
            'lastReadMessageId' => $state?->last_read_message_id ?? 0,
            'unreadCount' => $unread,
            // Jusqu'où chacun a lu : coches « vu » et détail « Vu par » dans l'app
            'reads' => (object) SessionChatStateModel::where('session_id', $sessionId)
                ->where('last_read_message_id', '>', 0)
                ->pluck('last_read_message_id', 'user_id')
                ->all(),
        ];
    }

    /**
     * Messages en ordre chronologique.
     *  - sans curseur : les derniers ;
     *  - before=N : la page précédente (on remonte l'historique) ;
     *  - after=N : tout ce qui a été posté depuis N (rattrapage).
     */
    public function messages(string $sessionId, string $userId, ?int $before = null, ?int $after = null): array
    {
        $this->assertMember($sessionId, $userId);

        $query = SessionChatMessageModel::with('user:id,firstname')->where('session_id', $sessionId);

        if ($after !== null) {
            $rows = $query->where('id', '>', $after)->orderBy('id')->limit(self::CATCH_UP_LIMIT + 1)->get();
            $hasMore = $rows->count() > self::CATCH_UP_LIMIT;
            $rows = $rows->take(self::CATCH_UP_LIMIT);
        } else {
            if ($before !== null) {
                $query->where('id', '<', $before);
            }
            $rows = $query->orderByDesc('id')->limit(self::PAGE_SIZE + 1)->get();
            $hasMore = $rows->count() > self::PAGE_SIZE;
            $rows = $rows->take(self::PAGE_SIZE)->reverse();
        }

        return [
            'messages' => $rows->map->toPayload()->values()->all(),
            // after : il reste des messages plus récents ; sinon : il reste des messages plus anciens
            'hasMore' => $hasMore,
        ];
    }

    /** @return array{message: array, created: bool} */
    public function send(string $sessionId, string $userId, string $clientId, string $body, array $mentions = []): array
    {
        $this->assertMember($sessionId, $userId);

        $body = trim($body);
        if ($body === '') {
            throw new ChatException('Le message est vide.', 422, 'EMPTY_MESSAGE');
        }
        if (mb_strlen($body) > self::MAX_LENGTH) {
            throw new ChatException('Message trop long (' . self::MAX_LENGTH . ' caractères max).', 422, 'MESSAGE_TOO_LONG');
        }

        // Déjà reçu (renvoi automatique après une coupure réseau) : on renvoie le même message
        $existing = $this->findByClientId($userId, $clientId);
        if ($existing) {
            return ['message' => $existing->toPayload(), 'created' => false];
        }

        $memberIds = SportSessionParticipantModel::where('session_id', $sessionId)->pluck('user_id')->all();
        $mentions = array_values(array_unique(array_filter(
            $mentions,
            fn ($id) => is_string($id) && $id !== $userId && in_array($id, $memberIds, true)
        )));

        try {
            $message = SessionChatMessageModel::create([
                'session_id' => $sessionId,
                'user_id' => $userId,
                'client_id' => $clientId,
                'body' => $body,
                'mentions' => $mentions ?: null,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            // Deux envois simultanés du même message : le premier a gagné
            return ['message' => $this->findByClientId($userId, $clientId)->toPayload(), 'created' => false];
        }

        $message->load('user:id,firstname');
        $payload = $message->toPayload();

        // Ses propres messages sont lus
        $this->markRead($sessionId, $userId, $message->id);

        try {
            broadcast(new ChatMessageSent($sessionId, $payload));
        } catch (\Throwable $e) {
            // Serveur temps réel indisponible : le message est enregistré, les autres le
            // récupéreront au prochain rattrapage
            Log::warning('Diffusion temps réel du message impossible', ['sessionId' => $sessionId, 'error' => $e->getMessage()]);
        }

        // Notifications après la réponse : l'envoi ne doit pas attendre Expo
        dispatch(fn () => app(self::class)->notifyNewMessage($sessionId, $message->id))->afterResponse();

        return ['message' => $payload, 'created' => true];
    }

    public function typing(string $sessionId, string $userId, string $firstname): void
    {
        $this->assertMember($sessionId, $userId);

        try {
            broadcast(new ChatUserTyping($sessionId, $userId, $firstname))->toOthers();
        } catch (\Throwable $e) {
            // Indicateur de saisie : sans importance s'il se perd
        }
    }

    public function markRead(string $sessionId, string $userId, int $messageId): void
    {
        $this->assertMember($sessionId, $userId);

        // Pas au-delà du dernier message réel de la session
        $messageId = min($messageId, (int) SessionChatMessageModel::where('session_id', $sessionId)->max('id'));

        SessionChatStateModel::firstOrCreate(['session_id' => $sessionId, 'user_id' => $userId]);
        // Mise à jour conditionnelle en une requête : deux appels simultanés (deux appareils, envoi +
        // lecture) ne peuvent pas faire reculer le pointeur de lecture
        $advanced = SessionChatStateModel::where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->where('last_read_message_id', '<', $messageId)
            ->update(['last_read_message_id' => $messageId, 'updated_at' => now()]);
        if ($advanced > 0) {
            try {
                broadcast(new ChatReadUpdated($sessionId, $userId, $messageId))->toOthers();
            } catch (\Throwable $e) {
                // Coches « vu » : rattrapées au prochain chargement si le temps réel est indisponible
            }
        }
    }

    public function setMuted(string $sessionId, string $userId, bool $muted): void
    {
        $this->assertMember($sessionId, $userId);

        SessionChatStateModel::updateOrCreate(
            ['session_id' => $sessionId, 'user_id' => $userId],
            ['muted' => $muted]
        );
    }

    public function isMember(string $sessionId, string $userId): bool
    {
        return SportSessionParticipantModel::where('session_id', $sessionId)->where('user_id', $userId)->exists();
    }

    public function notifyNewMessage(string $sessionId, int $messageId): void
    {
        // Une seule vague de notifications par message, quoi qu'il arrive (relance, double appel)
        if (!Cache::add('chat_notified:' . $messageId, true, now()->addDay())) {
            return;
        }

        $message = SessionChatMessageModel::with('user:id,firstname')->find($messageId);
        $session = SportSessionModel::find($sessionId);
        if (!$message || !$session) {
            return;
        }

        $firstname = $message->user?->firstname ?? 'Quelqu\'un';
        $sportName = DateFormatterService::getSportName($session->sport);
        $preview = Str::limit($message->body, self::PUSH_PREVIEW_LENGTH);
        $mentions = $message->mentions ?? [];

        // Mentionnés : notification complète (visible aussi dans l'onglet notifications)
        foreach ($mentions as $mentionedId) {
            try {
                $this->sessionNotificationService->notify(
                    $mentionedId,
                    $sessionId,
                    "{$firstname} t'a mentionné · {$sportName}",
                    $preview,
                    'chat_mention'
                );
            } catch (\Throwable $e) {
                // Un destinataire en erreur ne doit pas priver les autres de leur notification
                Log::error('Notification de mention impossible', ['sessionId' => $sessionId, 'userId' => $mentionedId, 'error' => $e->getMessage()]);
            }
        }

        // Les autres : push seulement (un message de chat n'a pas sa place dans l'onglet notifications)
        try {
            $this->pushToOthers($sessionId, $message, $mentions, "{$sportName} · {$firstname}", $preview);
        } catch (\Throwable $e) {
            Log::error('Push du message de chat impossible', ['sessionId' => $sessionId, 'error' => $e->getMessage()]);
        }
    }

    private function pushToOthers(string $sessionId, SessionChatMessageModel $message, array $mentions, string $title, string $preview): void
    {
        $mutedIds = SessionChatStateModel::where('session_id', $sessionId)->where('muted', true)->pluck('user_id')->all();
        $recipientIds = SportSessionParticipantModel::where('session_id', $sessionId)
            ->where('status', '!=', 'declined')
            ->where('user_id', '!=', $message->user_id)
            ->pluck('user_id')
            ->reject(fn ($id) => in_array($id, $mentions, true) || in_array($id, $mutedIds, true))
            ->values()
            ->all();

        if (empty($recipientIds)) {
            return;
        }

        $tokens = $this->pushTokenRepository->getTokensForUsers($recipientIds);
        if (!empty($tokens)) {
            $this->expoService->sendNotification($tokens, $title, $preview, [
                'type' => 'chat_message',
                'session_id' => $sessionId,
            ]);
        }
    }

    private function members(string $sessionId): array
    {
        $organizerId = SportSessionModel::where('id', $sessionId)->value('organizer_id');

        return SportSessionParticipantModel::with('user.profile.avatarFile')
            ->where('session_id', $sessionId)
            ->get()
            ->filter(fn ($p) => $p->user !== null)
            ->map(fn ($p) => [
                'id' => $p->user->id,
                'firstname' => $p->user->firstname,
                'lastname' => $p->user->lastname,
                'avatarUrl' => $p->user->profile?->avatarFile?->url,
                'status' => $p->status,
                'isOrganizer' => $p->user->id === $organizerId,
            ])
            ->values()
            ->all();
    }

    private function assertMember(string $sessionId, string $userId): void
    {
        if (!SportSessionModel::where('id', $sessionId)->exists()) {
            throw new ChatException('Session non trouvée', 404, 'SESSION_NOT_FOUND');
        }
        if (!$this->isMember($sessionId, $userId)) {
            throw new ChatException("Tu ne fais pas partie de cette session.", 403, 'FORBIDDEN');
        }
    }

    private function state(string $sessionId, string $userId): ?SessionChatStateModel
    {
        return SessionChatStateModel::where('session_id', $sessionId)->where('user_id', $userId)->first();
    }

    private function findByClientId(string $userId, string $clientId): ?SessionChatMessageModel
    {
        return SessionChatMessageModel::with('user:id,firstname')
            ->where('user_id', $userId)
            ->where('client_id', $clientId)
            ->first();
    }
}
