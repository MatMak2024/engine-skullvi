<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'talent' => fn () => [
                'isCandidate' => (bool) $user?->isCandidate(),
                'isSuspended' => (bool) $user?->candidate?->isSuspended(),
                'suspensionReason' => $user?->candidate?->suspension_reason,
                'candidateRemoved' => (bool) $user?->isPermanentlyRemovedAsCandidate(),
                'roles' => $user?->roles()->pluck('slug')->all() ?? [],
            ],
            'notifications' => fn () => $this->notifications($user),
        ];
    }

    /**
     * @return array{unread: int, items: list<array<string, mixed>>}
     */
    private function notifications(?User $user): array
    {
        if ($user === null) {
            return ['unread' => 0, 'items' => []];
        }

        return [
            'unread' => $user->unreadNotifications()->count(),
            'items' => $user->notifications()->latest()->limit(8)->get()->map(fn ($notification) => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Notification',
                'body' => $notification->data['body'] ?? '',
                'read' => $notification->read_at !== null,
            ])->all(),
        ];
    }
}
