<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function read(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()
            ?->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $item->markAsRead();

        $href = $item->data['href'] ?? null;
        $appUrl = rtrim((string) config('app.url'), '/');

        if (is_string($href) && (
            str_starts_with($href, $appUrl.'/')
            || (str_starts_with($href, '/') && ! str_starts_with($href, '//'))
        )) {
            return redirect($href);
        }

        return redirect()->route('dashboard');
    }
}
