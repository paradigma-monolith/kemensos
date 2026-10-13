<?php

namespace App\Http\Controllers;

use App\Notifications\SystemAlertNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get user notifications and unread count.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $user->notifications()->take(10)->get(),
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
        }

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success']);
        }

        return back();
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse|RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success']);
        }

        return back();
    }

    /**
     * Dispatch a demo real-time notification to the current user.
     */
    public function sendDemo(Request $request): JsonResponse|RedirectResponse
    {
        $request->user()->notify(new SystemAlertNotification(
            title: 'Notifikasi Real-Time Terkirim!',
            message: 'Pesan WebSocket ini disiarkan secara instan melalui Laravel Reverb pada ' . now()->format('H:i:s'),
            type: 'success',
            actionUrl: '/inertia/products'
        ));

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Notifikasi demo real-time berhasil dikirim via Reverb!',
            ]);
        }

        return back()->with('status', 'Notifikasi demo real-time berhasil dikirim via Reverb!');
    }
}
