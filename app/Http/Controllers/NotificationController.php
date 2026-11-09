<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('filter', 'all');
        $notifications = $request->user()->notifications()
            ->when($filter === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->paginate(15)->withQueryString();

        return view('notifications.index', compact('notifications', 'filter'));
    }

    public function read(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'unread' => $request->user()->unreadNotifications()->count()]);
        }

        $url = $notification->data['url'] ?? null;
        if ($url) {
            $path = parse_url($url, PHP_URL_PATH) ?: '/';
            $query = parse_url($url, PHP_URL_QUERY);

            return redirect($path.($query ? '?'.$query : ''));
        }

        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('toast', ['success', 'Semua notifikasi ditandai telah dibaca.']);
    }

    public function clear(Request $request): RedirectResponse
    {
        $request->user()->readNotifications()->delete();

        return back()->with('toast', ['success', 'Notifikasi yang sudah dibaca dihapus.']);
    }
}
