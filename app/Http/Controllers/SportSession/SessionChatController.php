<?php

namespace App\Http\Controllers\SportSession;

use App\Exceptions\ChatException;
use App\Http\Controllers\Controller;
use App\Services\SessionChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Chat d'une session (cf. SessionChatService) :
 *  GET  /api/sessions/{id}/chat            : membres, sourdine, non-lus
 *  GET  /api/sessions/{id}/chat/messages   : messages (?before=N historique, ?after=N rattrapage)
 *  POST /api/sessions/{id}/chat/messages   : envoie un message {clientId, body, mentions[]}
 *  POST /api/sessions/{id}/chat/read       : marque comme lu jusqu'à {messageId}
 *  PUT  /api/sessions/{id}/chat/mute       : coupe/réactive les notifications {muted}
 *  POST /api/sessions/{id}/chat/typing     : « est en train d'écrire »
 */
class SessionChatController extends Controller
{
    public function __construct(
        private SessionChatService $chat
    ) {}

    public function show(Request $request, string $id): JsonResponse
    {
        return $this->respond(fn () => $this->chat->meta($id, $request->user()->id));
    }

    public function messages(Request $request, string $id): JsonResponse
    {
        return $this->respond(function () use ($request, $id) {
            $data = $request->validate([
                'before' => ['nullable', 'integer', 'min:1'],
                'after' => ['nullable', 'integer', 'min:0'],
            ]);

            return $this->chat->messages(
                $id,
                $request->user()->id,
                isset($data['before']) ? (int) $data['before'] : null,
                isset($data['after']) ? (int) $data['after'] : null
            );
        });
    }

    public function send(Request $request, string $id): JsonResponse
    {
        return $this->respond(function () use ($request, $id) {
            $data = $request->validate([
                'clientId' => ['required', 'uuid'],
                'body' => ['required', 'string', 'max:' . (SessionChatService::MAX_LENGTH + 100)],
                'mentions' => ['nullable', 'array', 'max:50'],
                'mentions.*' => ['uuid'],
            ]);

            $result = $this->chat->send($id, $request->user()->id, $data['clientId'], $data['body'], $data['mentions'] ?? []);

            return $result['message'];
        }, fn ($message) => 201);
    }

    public function read(Request $request, string $id): JsonResponse
    {
        return $this->respond(function () use ($request, $id) {
            $data = $request->validate(['messageId' => ['required', 'integer', 'min:0']]);
            $this->chat->markRead($id, $request->user()->id, (int) $data['messageId']);
            return null;
        });
    }

    public function mute(Request $request, string $id): JsonResponse
    {
        return $this->respond(function () use ($request, $id) {
            $data = $request->validate(['muted' => ['required', 'boolean']]);
            $this->chat->setMuted($id, $request->user()->id, (bool) $data['muted']);
            return ['muted' => (bool) $data['muted']];
        });
    }

    public function typing(Request $request, string $id): JsonResponse
    {
        return $this->respond(function () use ($request, $id) {
            $this->chat->typing($id, $request->user()->id, (string) $request->user()->firstname);
            return null;
        });
    }

    private function respond(callable $action, ?callable $status = null): JsonResponse
    {
        try {
            $data = $action();
            return response()->json(['success' => true, 'data' => $data], $status ? $status($data) : 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Données invalides', 'details' => $e->errors()],
            ], 422);
        } catch (ChatException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => $e->errorCode, 'message' => $e->getMessage()],
            ], $e->status);
        }
    }
}
