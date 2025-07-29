<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\User\UserRepository;
use App\Repositories\UserProfile\UserProfileRepositoryInterface;
use App\Repositories\UserProfile\UserProfileRepository;
use App\Repositories\File\FileRepositoryInterface;
use App\Repositories\File\FileRepository;
use App\Repositories\Support\SupportRequestRepositoryInterface;
use App\Repositories\Support\SupportRequestRepository;
use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\SportSession\SportSessionRepository;
use App\Repositories\Notification\NotificationRepositoryInterface;
use App\Repositories\Notification\NotificationRepository;
use App\Repositories\Friend\FriendRepositoryInterface;
use App\Repositories\Friend\FriendRepository;
use App\Repositories\FriendRequest\FriendRequestRepositoryInterface;
use App\Repositories\FriendRequest\FriendRequestRepository;
use App\Repositories\SportSessionComment\SportSessionCommentRepositoryInterface;
use App\Repositories\SportSessionComment\SportSessionCommentRepository;
use App\Repositories\SportSessionPresence\SportSessionPresenceRepositoryInterface;
use App\Repositories\SportSessionPresence\SportSessionPresenceRepository;
use App\Repositories\PushToken\PushTokenRepositoryInterface;
use App\Repositories\PushToken\PushTokenRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register your repository bindings here
        // Example:
        // $this->app->bind(UserRepositoryInterface::class, UserRepository::class);

        // Example binding (uncomment and modify for your repositories)
        $this->app->bind(\App\Repositories\Example\ExampleRepositoryInterface::class, \App\Repositories\Example\ExampleRepository::class);

        // User, Profile and File repositories
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(UserProfileRepositoryInterface::class, UserProfileRepository::class);
        $this->app->bind(FileRepositoryInterface::class, FileRepository::class);
        $this->app->bind(SupportRequestRepositoryInterface::class, SupportRequestRepository::class);

        // Sport Session and Notification repositories
        $this->app->bind(SportSessionRepositoryInterface::class, SportSessionRepository::class);
        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);

        // Friend and FriendRequest repositories
        $this->app->bind(FriendRepositoryInterface::class, FriendRepository::class);
        $this->app->bind(FriendRequestRepositoryInterface::class, FriendRequestRepository::class);

        // Comment and Presence repositories
        $this->app->bind(SportSessionCommentRepositoryInterface::class, SportSessionCommentRepository::class);
        $this->app->bind(SportSessionPresenceRepositoryInterface::class, SportSessionPresenceRepository::class);

        // Push Token repository
        $this->app->bind(PushTokenRepositoryInterface::class, PushTokenRepository::class);

        // Services
        $this->app->singleton(\App\Services\SocketIOService::class, function ($app) {
            return new \App\Services\SocketIOService();
        });

        $this->app->singleton(\App\Services\ExpoPushNotificationService::class, function ($app) {
            return new \App\Services\ExpoPushNotificationService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}
