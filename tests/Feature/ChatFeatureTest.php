<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use App\Models\Message;
use App\Models\DirectMessage;
use App\Models\RoomInvitation;
use Tests\TestCase;

class ChatFeatureTest extends TestCase
{
    private User $admin;
    private User $user1;
    private User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        // Cria utilizadores de teste
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->user1 = User::factory()->create(['role' => 'cidadao']);
        $this->user2 = User::factory()->create(['role' => 'cidadao']);
    }

    // ======================== TESTES DE SALAS ========================

    public function test_admin_pode_criar_sala()
    {
        $this->actingAs($this->admin)
            ->post(route('chat.rooms.store'), [
                'name' => 'Sala Teste',
                'description' => 'Uma sala de teste',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rooms', [
            'name' => 'Sala Teste',
            'creator_id' => $this->admin->id,
        ]);
    }

    public function test_cidadao_nao_pode_criar_sala()
    {
        $this->actingAs($this->user1)
            ->post(route('chat.rooms.store'), [
                'name' => 'Sala Teste',
            ])
            ->assertForbidden();
    }

    public function test_utilizador_vê_salas_onde_é_membro()
    {
        $room = Room::factory()->create(['creator_id' => $this->admin->id]);
        $room->addMember($this->user1->id);

        $this->actingAs($this->user1)
            ->get(route('chat.rooms.show', $room))
            ->assertOk();
    }

    public function test_utilizador_nao_vê_salas_onde_nao_é_membro()
    {
        $room = Room::factory()->create(['creator_id' => $this->admin->id]);

        $this->actingAs($this->user1)
            ->get(route('chat.rooms.show', $room))
            ->assertForbidden();
    }

    public function test_admin_pode_adicionar_membro_a_sala()
    {
        $room = Room::factory()->create(['creator_id' => $this->admin->id]);
        $room->addMember($this->admin->id);

        $this->actingAs($this->admin)
            ->post(route('chat.rooms.add-member', $room), [
                'user_id' => $this->user1->id,
            ])
            ->assertOk();

        $this->assertTrue($room->hasMember($this->user1->id));
    }

    // ======================== TESTES DE MENSAGENS ========================

    public function test_membro_pode_enviar_mensagem()
    {
        $room = Room::factory()->create(['creator_id' => $this->admin->id]);
        $room->addMember($this->user1->id);

        $this->actingAs($this->user1)
            ->post(route('chat.messages.store', $room), [
                'content' => 'Olá a todos!',
            ])
            ->assertOk();

        $this->assertDatabaseHas('messages', [
            'room_id' => $room->id,
            'user_id' => $this->user1->id,
            'content' => 'Olá a todos!',
        ]);
    }

    public function test_nao_membro_nao_pode_enviar_mensagem()
    {
        $room = Room::factory()->create(['creator_id' => $this->admin->id]);

        $this->actingAs($this->user1)
            ->post(route('chat.messages.store', $room), [
                'content' => 'Olá!',
            ])
            ->assertForbidden();
    }

    public function test_autor_pode_editar_mensagem()
    {
        $room = Room::factory()->create(['creator_id' => $this->admin->id]);
        $room->addMember($this->user1->id);

        $message = Message::factory()->create([
            'room_id' => $room->id,
            'user_id' => $this->user1->id,
        ]);

        $this->actingAs($this->user1)
            ->patch(route('chat.messages.update', [$room, $message]), [
                'content' => 'Mensagem editada',
            ])
            ->assertOk();

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'content' => 'Mensagem editada',
        ]);
    }

    public function test_outro_utilizador_nao_pode_editar_mensagem()
    {
        $room = Room::factory()->create(['creator_id' => $this->admin->id]);
        $room->addMember($this->user1->id);
        $room->addMember($this->user2->id);

        $message = Message::factory()->create([
            'room_id' => $room->id,
            'user_id' => $this->user1->id,
        ]);

        $this->actingAs($this->user2)
            ->patch(route('chat.messages.update', [$room, $message]), [
                'content' => 'Mensagem editada',
            ])
            ->assertForbidden();
    }

    // ======================== TESTES DE MENSAGENS DIRETAS ========================

    public function test_utilizador_pode_enviar_mensagem_direta()
    {
        $this->actingAs($this->user1)
            ->post(route('chat.direct-messages.store', $this->user2), [
                'content' => 'Olá! Tudo bem?',
            ])
            ->assertOk();

        $this->assertDatabaseHas('direct_messages', [
            'sender_id' => $this->user1->id,
            'recipient_id' => $this->user2->id,
            'content' => 'Olá! Tudo bem?',
        ]);
    }

    public function test_utilizador_nao_pode_enviar_mensagem_para_si_proprio()
    {
        $this->actingAs($this->user1)
            ->post(route('chat.direct-messages.store', $this->user1), [
                'content' => 'Olá para mim mesmo',
            ])
            ->assertStatus(422);
    }

    public function test_mensagem_direta_inicia_sem_leitura()
    {
        DirectMessage::create([
            'sender_id' => $this->user1->id,
            'recipient_id' => $this->user2->id,
            'content' => 'Teste',
        ]);

        $this->assertTrue(
            DirectMessage::first()->isRead() === false
        );
    }

    public function test_pode_marcar_mensagem_direta_como_lida()
    {
        $message = DirectMessage::create([
            'sender_id' => $this->user1->id,
            'recipient_id' => $this->user2->id,
            'content' => 'Teste',
        ]);

        $message->markAsRead();
        $this->assertTrue($message->isRead());
    }

    // ======================== TESTES DE CONVITES ========================

    public function test_admin_pode_convidar_utilizador_para_sala()
    {
        $room = Room::factory()->create(['creator_id' => $this->admin->id]);
        $room->addMember($this->admin->id);

        $this->actingAs($this->admin)
            ->post(route('chat.invitations.store', $room), [
                'users' => [$this->user1->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('room_invitations', [
            'room_id' => $room->id,
            'invited_user_id' => $this->user1->id,
            'status' => 'pending',
        ]);
    }

    public function test_utilizador_nao_pode_convidar_para_sala()
    {
        $room = Room::factory()->create(['creator_id' => $this->admin->id]);
        $room->addMember($this->user1->id);

        $this->actingAs($this->user1)
            ->post(route('chat.invitations.store', $room), [
                'users' => [$this->user2->id],
            ])
            ->assertForbidden();
    }

    public function test_utilizador_pode_aceitar_convite()
    {
        $room = Room::factory()->create(['creator_id' => $this->admin->id]);

        $invitation = RoomInvitation::create([
            'room_id' => $room->id,
            'invited_user_id' => $this->user1->id,
            'invited_by_id' => $this->admin->id,
        ]);

        $this->actingAs($this->user1)
            ->post(route('chat.invitations.accept', $invitation))
            ->assertRedirect();

        $this->assertTrue($room->hasMember($this->user1->id));
        $this->assertDatabaseHas('room_invitations', [
            'id' => $invitation->id,
            'status' => 'accepted',
        ]);
    }

    public function test_utilizador_pode_recusar_convite()
    {
        $room = Room::factory()->create(['creator_id' => $this->admin->id]);

        $invitation = RoomInvitation::create([
            'room_id' => $room->id,
            'invited_user_id' => $this->user1->id,
            'invited_by_id' => $this->admin->id,
        ]);

        $this->actingAs($this->user1)
            ->post(route('chat.invitations.decline', $invitation))
            ->assertRedirect();

        $this->assertFalse($room->hasMember($this->user1->id));
        $this->assertDatabaseHas('room_invitations', [
            'id' => $invitation->id,
            'status' => 'declined',
        ]);
    }

    // ======================== TESTES DE PERMISSÕES ========================

    public function test_apenas_admin_ou_criador_pode_editar_sala()
    {
        $room = Room::factory()->create(['creator_id' => $this->admin->id]);

        // Admin pode editar
        $this->actingAs($this->admin)
            ->get(route('chat.rooms.edit', $room))
            ->assertOk();

        // Outro utilizador não pode
        $room->addMember($this->user1->id);
        $this->actingAs($this->user1)
            ->get(route('chat.rooms.edit', $room))
            ->assertForbidden();
    }

    public function test_apenas_admin_ou_criador_pode_eliminar_sala()
    {
        $room = Room::factory()->create(['creator_id' => $this->admin->id]);

        // Admin pode eliminar
        $this->actingAs($this->admin)
            ->delete(route('chat.rooms.destroy', $room))
            ->assertRedirect();

        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }
}
