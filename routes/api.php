<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\RefreshTokenController;
use App\Http\Controllers\Example\FindAllExamplesAction;
use App\Http\Controllers\Profile\FindUserProfileAction;
use App\Http\Controllers\Profile\UpdateUserAvatarAction;
use App\Http\Controllers\Support\CreateSupportRequestAction;
use App\Http\Controllers\Support\FindAllSupportRequestsAction;
use App\Http\Controllers\User\DeleteUserDataAction;
use App\Http\Controllers\User\FindUserByIdAction;
use App\Http\Controllers\Version\VersionCheckAction;

// Sport Session Controllers
use App\Http\Controllers\SportSession\CreateSportSessionAction;
use App\Http\Controllers\SportSession\FindAllSportSessionsAction;
use App\Http\Controllers\SportSession\FindSportSessionByIdAction;
use App\Http\Controllers\SportSession\UpdateSportSessionAction;
use App\Http\Controllers\SportSession\DeleteSportSessionAction;
use App\Http\Controllers\SportSession\RespondToSessionInvitationAction;
use App\Http\Controllers\SportSession\AddSessionCommentAction;
use App\Http\Controllers\SportSession\InviteUsersToSessionAction;
use App\Http\Controllers\SportSession\FindMyCreatedSessionsAction;
use App\Http\Controllers\SportSession\FindMyParticipationsAction;
use App\Http\Controllers\SportSession\FindMyHistoryAction;

// Comment Controllers
use App\Http\Controllers\SportSession\CreateCommentAction;
use App\Http\Controllers\SportSession\GetCommentsAction;
use App\Http\Controllers\SportSession\UpdateCommentAction;
use App\Http\Controllers\SportSession\DeleteCommentAction;

// Presence Controllers
use App\Http\Controllers\SportSession\JoinSessionAction;
use App\Http\Controllers\SportSession\LeaveSessionAction;
use App\Http\Controllers\SportSession\UpdateTypingStatusAction;
use App\Http\Controllers\SportSession\GetOnlineUsersAction;

// Notification Controllers
use App\Http\Controllers\Notification\FindUserNotificationsAction;
use App\Http\Controllers\Notification\MarkNotificationAsReadAction;
use App\Http\Controllers\Notification\MarkAllNotificationsAsReadAction;
use App\Http\Controllers\Notification\DeleteNotificationAction;
use App\Http\Controllers\Notification\GetUnreadCountAction;
use App\Http\Controllers\Notification\PushNotificationAction;
use App\Http\Controllers\Notification\SendPushNotificationAction;

// User Controllers
use App\Http\Controllers\User\GetUserProfileAction;
use App\Http\Controllers\User\UpdateUserProfileAction;
use App\Http\Controllers\User\GetUserFriendsAction;
use App\Http\Controllers\User\GetFriendRequestsAction;
use App\Http\Controllers\User\SendFriendRequestAction;
use App\Http\Controllers\User\RespondToFriendRequestAction;
use App\Http\Controllers\User\SearchUsersAction;
use App\Http\Controllers\User\UpdateUserEmailAction;
use App\Http\Controllers\User\UpdateUserPasswordAction;
use App\Http\Controllers\User\DeleteUserAction;
use App\Http\Controllers\User\CancelFriendRequestAction;
use App\Http\Controllers\User\RemoveFriendAction;

// Push Token Controllers
use App\Http\Controllers\PushToken\SavePushTokenAction;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Auth routes
Route::post('/register', [RegisteredUserController::class, 'store']);
Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);
Route::post('/reset-password', [NewPasswordController::class, 'store']);

// Version (publique)
Route::get('/version', VersionCheckAction::class);

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Auth
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
    Route::post('/auth/refresh', [RefreshTokenController::class, '__invoke']);
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store']);
    Route::get('/verify-email/{id}/{hash}', [VerifyEmailController::class, '__invoke']);

    // Examples
    Route::get('/examples', FindAllExamplesAction::class);

    // Profile
    Route::get('/profile', GetUserProfileAction::class);
    Route::put('/profile/avatar', UpdateUserAvatarAction::class);

    // Support
    Route::post('/support', CreateSupportRequestAction::class);
    Route::get('/support', FindAllSupportRequestsAction::class);

    // User
    Route::delete('/users/data', DeleteUserDataAction::class);

    // Sport Sessions
    Route::get('/sessions', FindAllSportSessionsAction::class);
    Route::get('/sessions/my-created', FindMyCreatedSessionsAction::class);
    Route::get('/sessions/my-participations', FindMyParticipationsAction::class);
    Route::get('/sessions/history', FindMyHistoryAction::class);
    Route::get('/sessions/{id}', FindSportSessionByIdAction::class);
    Route::post('/sessions', CreateSportSessionAction::class);
    Route::put('/sessions/{id}', UpdateSportSessionAction::class);
    Route::delete('/sessions/{id}', DeleteSportSessionAction::class);
    Route::post('/sessions/{id}/invite', InviteUsersToSessionAction::class);
    Route::patch('/sessions/{id}/respond', RespondToSessionInvitationAction::class);

    // Commentaires en temps réel
    Route::get('/sessions/{sessionId}/comments', GetCommentsAction::class);
    Route::post('/sessions/{sessionId}/comments', CreateCommentAction::class);
    Route::put('/sessions/{sessionId}/comments/{commentId}', UpdateCommentAction::class);
    Route::delete('/sessions/{sessionId}/comments/{commentId}', DeleteCommentAction::class);

    // Présence en temps réel
    Route::post('/sessions/{sessionId}/presence/join', JoinSessionAction::class);
    Route::post('/sessions/{sessionId}/presence/leave', LeaveSessionAction::class);
    Route::post('/sessions/{sessionId}/presence/typing', UpdateTypingStatusAction::class);
    Route::get('/sessions/{sessionId}/presence/users', GetOnlineUsersAction::class);

    // Notifications
    Route::get('/notifications', FindUserNotificationsAction::class);
    Route::patch('/notifications/{id}/read', MarkNotificationAsReadAction::class);
    Route::patch('/notifications/read-all', MarkAllNotificationsAsReadAction::class);
    Route::delete('/notifications/{id}', DeleteNotificationAction::class);
    Route::get('/notifications/unread-count', GetUnreadCountAction::class);
    Route::post('/notifications/push', PushNotificationAction::class);
    Route::post('/notifications/send', SendPushNotificationAction::class);

    // User Management (nouveaux endpoints)
    Route::get('/users/profile', GetUserProfileAction::class);
    Route::put('/users/profile', UpdateUserProfileAction::class);
    Route::get('/users/friends', GetUserFriendsAction::class);
    Route::delete('/users/friends/{friendId}', RemoveFriendAction::class);
    Route::get('/users/friend-requests', GetFriendRequestsAction::class);
    Route::post('/users/friend-requests', SendFriendRequestAction::class);
    Route::patch('/users/friend-requests/{id}', RespondToFriendRequestAction::class);
    Route::delete('/users/friend-requests', CancelFriendRequestAction::class);
    Route::get('/users/search', SearchUsersAction::class);
    Route::post('/users/update-email', UpdateUserEmailAction::class);
    Route::post('/users/update-password', UpdateUserPasswordAction::class);
    Route::delete('/users', DeleteUserAction::class);
    Route::get('/users/{id}', FindUserByIdAction::class);

    // Push Tokens
    Route::post('/push-tokens', SavePushTokenAction::class);
});
