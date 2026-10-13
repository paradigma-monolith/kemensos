<?php

namespace Tests\Feature;

use App\Events\ChatMessageSent;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;
    public function test_unauthenticated_user_cannot_access_chat(): void
    {
        $response = $this->getJson('/chat/messages');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_fetch_chat_history(): void
    {
        $user = User::factory()->create();

        ChatMessage::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_avatar' => 'https://ui-avatars.com/api/?name=Test',
            'message' => 'Pesan pengujian real-time',
        ]);

        $response = $this->actingAs($user)->getJson('/chat/messages');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'user_name',
                        'user_avatar',
                        'message',
                        'created_at',
                    ],
                ],
            ])
            ->assertJsonFragment([
                'message' => 'Pesan pengujian real-time',
            ]);
    }

    public function test_authenticated_user_can_send_chat_message_and_broadcast_event(): void
    {
        Event::fake([ChatMessageSent::class]);

        $user = User::factory()->create([
            'name' => 'Developer Hybrid',
        ]);

        $payload = [
            'message' => 'Halo Reverb WebSocket!',
        ];

        $response = $this->actingAs($user)->postJson('/chat/messages', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => [
                    'user_id' => $user->id,
                    'user_name' => 'Developer Hybrid',
                    'message' => 'Halo Reverb WebSocket!',
                ],
            ]);

        $this->assertDatabaseHas('chat_messages', [
            'user_id' => $user->id,
            'message' => 'Halo Reverb WebSocket!',
        ]);

        Event::assertDispatched(ChatMessageSent::class, function ($event) use ($user) {
            $channels = $event->broadcastOn();
            return $event->chatMessage->message === 'Halo Reverb WebSocket!'
                && $event->broadcastAs() === 'chat.message'
                && count($channels) === 1
                && $channels[0]->name === 'chat-channel';
        });
    }

    public function test_sending_empty_chat_message_fails_validation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/chat/messages', [
            'message' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_authenticated_user_can_trigger_demo_bot_reply(): void
    {
        Event::fake([ChatMessageSent::class]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/chat/demo-reply');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message' => [
                    'id',
                    'user_name',
                    'user_avatar',
                    'message',
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('chat_messages', [
            'user_name' => 'Reverb Bot (Auto)',
        ]);

        Event::assertDispatched(ChatMessageSent::class, function ($event) {
            return $event->chatMessage->user_name === 'Reverb Bot (Auto)'
                && $event->broadcastAs() === 'chat.message';
        });
    }

    public function test_staff_user_can_send_chat_message(): void
    {
        Event::fake([ChatMessageSent::class]);

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $staff = User::where('email', 'staff@hybrid.local')->first();
        $this->assertNotNull($staff);
        $this->assertTrue($staff->hasRole('Staff'));
        $this->assertTrue($staff->hasPermissionTo('send chat'));

        $response = $this->actingAs($staff)->postJson('/chat/messages', [
            'message' => 'Halo dari Staff Operator!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => [
                    'user_id' => $staff->id,
                    'user_name' => $staff->name,
                    'message' => 'Halo dari Staff Operator!',
                ],
            ]);

        $this->assertDatabaseHas('chat_messages', [
            'user_id' => $staff->id,
            'message' => 'Halo dari Staff Operator!',
        ]);
    }
}
