<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageSent;
use App\Models\ChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    /**
     * Get recent chat messages history.
     */
    public function index(): JsonResponse
    {
        $messages = ChatMessage::latest()->take(50)->get()->reverse()->values();

        return response()->json([
            'status' => 'success',
            'data' => $messages->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'user_id' => $msg->user_id,
                    'user_name' => $msg->user_name,
                    'user_avatar' => $msg->user_avatar,
                    'message' => $msg->message,
                    'created_at' => $msg->created_at->format('H:i'),
                ];
            }),
        ]);
    }

    /**
     * Store and broadcast a new chat message via Reverb WebSocket.
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        $message = ChatMessage::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'Pengunjung',
            'user_avatar' => $user?->avatar_url ?? 'https://ui-avatars.com/api/?name=Guest&background=6366f1&color=fff',
            'message' => trim($request->input('message')),
        ]);

        // Broadcast to Reverb WebSocket channel with safe fallback
        try {
            broadcast(new ChatMessageSent($message))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('[Reverb Broadcast] Server Reverb belum aktif: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => [
                'id' => $message->id,
                'user_id' => $message->user_id,
                'user_name' => $message->user_name,
                'user_avatar' => $message->user_avatar,
                'message' => $message->message,
                'created_at' => $message->created_at->format('H:i'),
            ],
        ]);
    }

    /**
     * Dispatch an automated instant bot reply for testing real-time WebSocket reception.
     */
    public function demoReply(): JsonResponse
    {
        $sampleReplies = [
            'Halo! Laravel Reverb menyiarkan pesan ini secara real-time via WebSocket 🚀',
            'Sub-10ms response time tercapai dengan arsitektur hybrid modern! ⚡',
            'Sistem starter kit hybrid siap digunakan untuk modul enterprise apapun. 👍',
            'Pesan ini tersimpan di MySQL sekaligus disiarkan ke channel "chat-channel". ✨',
        ];

        $replyText = $sampleReplies[array_rand($sampleReplies)];

        $message = ChatMessage::create([
            'user_id' => null,
            'user_name' => 'Reverb Bot (Auto)',
            'user_avatar' => 'https://ui-avatars.com/api/?name=Reverb+Bot&background=10b981&color=fff',
            'message' => $replyText,
        ]);

        try {
            broadcast(new ChatMessageSent($message));
        } catch (\Throwable $e) {
            Log::warning('[Reverb Broadcast Demo] Server Reverb belum aktif: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => [
                'id' => $message->id,
                'user_id' => $message->user_id,
                'user_name' => $message->user_name,
                'user_avatar' => $message->user_avatar,
                'message' => $message->message,
                'created_at' => $message->created_at->format('H:i'),
            ],
        ]);
    }
}
