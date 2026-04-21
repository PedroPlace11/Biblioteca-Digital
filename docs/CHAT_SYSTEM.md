# Sistema de Chat - Documentação

## Visão Geral

Sistema completo de chat integrado na aplicação de biblioteca, permitindo:
- **Salas de Chat**: Espaços de comunicação em grupo
- **Mensagens Diretas**: Conversas privadas entre utilizadores
- **Convites**: Sistema de convites para salas (gerido por admins)
- **Notificações**: Notificações de convites via email e base de dados

---

## Tabelas de Base de Dados

### 1. `rooms`
Salas de chat compartilhadas

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | bigint | Identificador único |
| `name` | string | Nome da sala (único) |
| `description` | text | Descrição da sala |
| `avatar` | string | Caminho para avatar/imagem |
| `creator_id` | bigint FK | Admin que criou a sala |
| `is_archived` | boolean | Indica se sala está arquivada |
| `created_at` | timestamp | Data de criação |
| `updated_at` | timestamp | Data de atualização |

### 2. `room_users`
Relacionamento N:N entre utilizadores e salas

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `room_id` | bigint FK | ID da sala |
| `user_id` | bigint FK | ID do utilizador |
| `joined_at` | timestamp | Data que entrou na sala |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

### 3. `messages`
Mensagens em salas

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | bigint | Identificador único |
| `room_id` | bigint FK | Sala onde foi enviada |
| `user_id` | bigint FK | Utilizador que enviou |
| `content` | text | Conteúdo da mensagem |
| `type` | enum | 'text', 'file', 'image' |
| `file_path` | string | Caminho do arquivo (se tipo=file/image) |
| `file_name` | string | Nome original do arquivo |
| `mime_type` | string | Tipo MIME do arquivo |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

### 4. `direct_messages`
Mensagens diretas entre utilizadores

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | bigint | Identificador único |
| `sender_id` | bigint FK | Utilizador que enviou |
| `recipient_id` | bigint FK | Utilizador que recebeu |
| `content` | text | Conteúdo da mensagem |
| `type` | enum | 'text', 'file', 'image' |
| `file_path` | string | Caminho do arquivo |
| `file_name` | string | Nome original do arquivo |
| `mime_type` | string | Tipo MIME do arquivo |
| `read_at` | timestamp | Data/hora de leitura (NULL = não lida) |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

### 5. `room_invitations`
Convites para salas

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | bigint | Identificador único |
| `room_id` | bigint FK | Sala do convite |
| `invited_user_id` | bigint FK | Utilizador convidado |
| `invited_by_id` | bigint FK | Admin que convidou |
| `status` | enum | 'pending', 'accepted', 'declined' |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

---

## Modelos (Models)

### Room
```php
// Relacionamentos
$room->creator()              // Admin que criou
$room->users()                // Utilizadores membros
$room->messages()             // Mensagens enviadas
$room->invitations()          // Convites pendentes

// Métodos úteis
$room->hasMember($userId)     // Verifica se utilizador é membro
$room->addMember($userId)     // Adiciona utilizador
$room->removeMember($userId)  // Remove utilizador
```

### Message
```php
// Relacionamentos
$message->room()              // Sala onde foi enviada
$message->user()              // Utilizador que enviou

// Métodos úteis
$message->isFileMessage()     // Verifica se é arquivo
$message->isImageMessage()    // Verifica se é imagem
$message->getFileUrlAttribute() // URL do arquivo
```

### DirectMessage
```php
// Relacionamentos
$message->sender()            // Quem enviou
$message->recipient()         // Quem recebeu

// Métodos úteis
$message->markAsRead()        // Marca como lida
$message->isRead()            // Verifica se foi lida
$message->isFileMessage()     // Verifica se é arquivo
$message->isImageMessage()    // Verifica se é imagem
```

### RoomInvitation
```php
// Relacionamentos
$invitation->room()           // Sala do convite
$invitation->invitedUser()    // Utilizador convidado
$invitation->invitedBy()      // Admin que convidou

// Métodos úteis
$invitation->accept()         // Aceita convite
$invitation->decline()        // Recusa convite
$invitation->isPending()      // Verifica se está pendente
```

---

## Controladores

### RoomController
Gerencia CRUD de salas

**Endpoints:**
- `GET /chat/rooms` → Lista salas do utilizador
- `GET /chat/rooms/{room}` → Mostra sala com mensagens
- `POST /chat/rooms` → Cria nova sala (admin only)
- `GET /chat/rooms/{room}/edit` → Formulário de edição
- `PATCH /chat/rooms/{room}` → Atualiza sala
- `DELETE /chat/rooms/{room}` → Elimina sala
- `POST /chat/rooms/{room}/archive` → Arquiva sala
- `POST /chat/rooms/{room}/members` → Adiciona membro (admin only)
- `DELETE /chat/rooms/{room}/members/{user}` → Remove membro
- `GET /chat/rooms/{room}/available-users` → Lista utilizadores para convidar

### MessageController
Gerencia mensagens em salas

**Endpoints:**
- `POST /chat/rooms/{room}/messages` → Envia mensagem
- `GET /chat/rooms/{room}/messages` → Obtém mensagens (últimas 50)
- `GET /chat/rooms/{room}/messages/new` → Obtém mensagens desde timestamp (real-time)
- `PATCH /chat/rooms/{room}/messages/{message}` → Edita mensagem
- `DELETE /chat/rooms/{room}/messages/{message}` → Apaga mensagem

### DirectMessageController
Gerencia mensagens diretas

**Endpoints:**
- `GET /chat/direct-messages` → Lista conversas do utilizador
- `GET /chat/direct-messages/{user}` → Mostra conversa com utilizador
- `POST /chat/direct-messages/{user}` → Envia mensagem direta
- `GET /chat/direct-messages/{user}/new` → Obtém novas mensagens (real-time)
- `PATCH /chat/direct-messages/{user}/{message}` → Edita mensagem
- `DELETE /chat/direct-messages/{user}/{message}` → Apaga mensagem
- `POST /chat/direct-messages/{user}/mark-as-read` → Marca como lida
- `GET /chat/direct-messages/unread/count` → Conta mensagens não lidas

### RoomInvitationController
Gerencia convites

**Endpoints:**
- `GET /chat/invitations` → Lista convites pendentes
- `POST /chat/rooms/{room}/invitations` → Cria convites (admin only)
- `POST /chat/invitations/{invitation}/accept` → Aceita convite
- `POST /chat/invitations/{invitation}/decline` → Recusa convite
- `DELETE /chat/rooms/{room}/invitations/{invitation}` → Remove convite (admin only)

---

## Políticas de Autorização (Policies)

### RoomPolicy
- `view($user, $room)` → Apenas membros da sala
- `create($user)` → Apenas admins
- `update($user, $room)` → Criador ou admin
- `delete($user, $room)` → Criador ou admin
- `invite($user, $room)` → Criador ou admin

### MessagePolicy
- `update($user, $message)` → Apenas o autor
- `delete($user, $message)` → Autor ou admin

### DirectMessagePolicy
- `update($user, $message)` → Apenas o sender
- `delete($user, $message)` → Apenas o sender

---

## Notificações

### RoomInvitationNotification
Enviada quando um utilizador é convidado para uma sala

**Canais:**
- **Database**: Armazenada na tabela `notifications`
- **Mail**: Enviada por email

**Dados da notificação:**
```php
[
    'room_id' => 123,
    'room_name' => 'Reunião Semanal',
    'invited_by' => 'João Silva',
    'invitation_id' => 456,
]
```

---

## Testes

Teste completo em `tests/Feature/ChatFeatureTest.php`

**Testes incluem:**
- ✅ Admin pode criar sala
- ✅ Cidadão não pode criar sala
- ✅ Utilizador vê apenas salas onde é membro
- ✅ Membro pode enviar mensagem
- ✅ Não-membro não pode enviar mensagem
- ✅ Autor pode editar sua mensagem
- ✅ Outro utilizador não pode editar mensagem de terceiro
- ✅ Utilizador pode enviar mensagem direta
- ✅ Não pode enviar mensagem para si próprio
- ✅ Admin pode convidar utilizador
- ✅ Utilizador pode aceitar/recusar convite
- ✅ Permissões de edição e eliminação

**Executar testes:**
```bash
php artisan test tests/Feature/ChatFeatureTest.php
```

---

## Fluxo de Uso

### Criar Sala (Admin)
1. Admin acede a `/chat/rooms/create`
2. Preenche formulário (nome, descrição, avatar)
3. Clica "Criar"
4. Admin é adicionado automaticamente como membro

### Convidar Utilizadores (Admin)
1. Admin vai para sala `/chat/rooms/{room}`
2. Clica "Convidar Utilizadores"
3. Seleciona utilizadores para convidar
4. Utilizadores recebem notificação por email + dashboard

### Utilizador Recebe Convite
1. Utilizador recebe email com convite
2. Ou vê na dashboard em `/chat/invitations`
3. Pode aceitar ou recusar
4. Se aceitar, é adicionado à sala

### Enviar Mensagem em Sala
1. Membro vai para `/chat/rooms/{room}`
2. Escreve mensagem
3. Clica "Enviar"
4. Mensagem aparece em tempo real para todos os membros

### Mensagem Direta
1. Utilizador vai para `/chat/direct-messages`
2. Seleciona contacto ou começa nova conversa
3. Escreve mensagem
4. Outro utilizador vê notificação de nova mensagem

---

## Configuração Necessária

### Migrations
Execute as migrações para criar as tabelas:
```bash
php artisan migrate
```

### Queues (Notificações)
Se quiser enviar notificações em background:
```bash
# .env
QUEUE_CONNECTION=database

# Executar worker
php artisan queue:work
```

### Storage (Arquivos)
Crie link simbólico para arquivos:
```bash
php artisan storage:link
```

---

## Próximos Passos (Futuro)

1. **Real-time com WebSockets**: Integrar Laravel Reverb para atualizações em tempo real sem polling
2. **Componentes Livewire**: Criar componentes para UI interativa
3. **Views Blade**: Criar templates para salas, mensagens, convites
4. **Busca Avançada**: Filtrar mensagens por utilizador, data, conteúdo
5. **Reações**: Adicionar sistema de reações a mensagens (emojis)
6. **Reencaminhamento**: Reencaminhar mensagens entre salas
7. **Pinned Messages**: Mensagens fixadas no topo da sala
8. **Typing Indicators**: Indicador de "está a escrever"
9. **Permissões Granulares**: Permissões por utilizador (mod, silenciado, etc)
10. **Backup de Mensagens**: Exportar histórico de chat

---

## Troubleshooting

### Mensagens não aparecem em tempo real
- Sistema usa polling com Livewire
- Pode se integrar WebSockets para real-time puro
- Verifique `QUEUE_CONNECTION` para notificações

### Arquivos não são salvos
- Verifique permissões em `storage/app/public`
- Execute `php artisan storage:link`
- Confirme `FILESYSTEM_DISK=public` em `.env`

### Notificações não chegam
- Verifique configuração de email em `.env`
- Se usando queue, execute `php artisan queue:work`
- Consulte logs em `storage/logs/laravel.log`

---

## Suporte

Para adicionar funcionalidades ou reportar bugs, consulte os controladores e políticas no código.

**Autor**: Sistema de Chat - Biblioteca  
**Data de Criação**: Abril 2025  
**Última Atualização**: Abril 20, 2025
