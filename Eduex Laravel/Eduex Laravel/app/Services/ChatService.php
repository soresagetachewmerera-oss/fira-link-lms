<?php

namespace App\Services;

use App\Models\ChatAttachment;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Enrollment;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ChatService
{
    public function __construct(private SettingsRepository $settings)
    {
    }

    /**
     * Get chat config from admin settings.
     */
    public function getConfig(string $key = null, $default = null)
    {
        $config = $this->settings->get('chat.config', [
            'enabled'            => true,
            'poll_timeout'       => 25,
            'poll_interval'      => 2,
            'max_file_size'      => 10240,
            'messages_per_page'  => 50,
            'allowed_file_types' => 'jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip',
        ]);

        if ($key) {
            return $config[$key] ?? $default;
        }

        return $config;
    }

    /**
     * Get conversations for a user.
     */
    public function getConversations(int $userId, string $role, bool $accepted = true): LengthAwarePaginator
    {
        $query = Conversation::query()
            ->with(['student:id,name,profile_photo', 'instructor:id,name,profile_photo', 'latestMessage'])
            ->where('is_closed', false);

        if ($role === 'student') {
            $query->where('student_id', $userId);
        } else {
            $query->where('instructor_id', $userId);
        }

        if ($accepted) {
            $query->where('is_accepted', true);
        } else {
            $query->where('is_accepted', false);
        }

        return $query->orderByDesc('last_message_at')
            ->paginate(20);
    }

    /**
     * Get or create a conversation between student and instructor.
     * Auto-sets is_accepted based on enrollment status.
     */
    public function getOrCreateConversation(int $studentId, int $instructorId): Conversation
    {
        $conversation = Conversation::where('student_id', $studentId)
            ->where('instructor_id', $instructorId)
            ->first();

        if ($conversation) {
            return $conversation;
        }

        // Check if student is enrolled in any course by this instructor
        $isEnrolled = Enrollment::whereHas('course', function ($q) use ($instructorId) {
                $q->where('instructor_id', $instructorId);
            })
            ->where('user_id', $studentId)
            ->whereIn('status', [Enrollment::STATUS_APPROVED, Enrollment::STATUS_COMPLETED])
            ->exists();

        return Conversation::create([
            'student_id'    => $studentId,
            'instructor_id' => $instructorId,
            'is_accepted'   => $isEnrolled,
        ]);
    }

    /**
     * Get messages for a conversation with pagination.
     */
    public function getMessages(int $conversationId, int $userId, int $limit = null): Collection
    {
        $limit = $limit ?? (int) $this->getConfig('messages_per_page', 50);

        // Mark incoming messages as read
        $this->markAsRead($conversationId, $userId);

        return ChatMessage::where('conversation_id', $conversationId)
            ->with(['sender:id,name,profile_photo', 'attachments'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Send a message with optional file attachments.
     */
    public function sendMessage(int $conversationId, int $senderId, ?string $body, array $files = []): ChatMessage
    {
        $message = ChatMessage::create([
            'conversation_id' => $conversationId,
            'sender_id'       => $senderId,
            'body'            => $body,
        ]);

        // Handle file attachments
        if (!empty($files)) {
            $this->storeAttachments($message, $files);
        }

        // Update conversation last_message_at
        Conversation::where('id', $conversationId)
            ->update(['last_message_at' => now()]);

        // Reload with relations
        return $message->load(['sender:id,name,profile_photo', 'attachments']);
    }

    /**
     * Long poll for new messages (production) or short poll (dev).
     */
    public function pollMessages(int $conversationId, int $userId, int $lastMessageId): Collection
    {
        $maxWait = app()->environment('local')
            ? 0
            : (int) $this->getConfig('poll_timeout', 25);
        $interval = (int) $this->getConfig('poll_interval', 2);
        $elapsed = 0;

        while ($elapsed < $maxWait) {
            $messages = $this->fetchNewMessages($conversationId, $userId, $lastMessageId);

            if ($messages->isNotEmpty()) {
                $this->markAsRead($conversationId, $userId);
                return $messages;
            }

            sleep($interval);
            $elapsed += $interval;
        }

        // Final check (covers short poll and end-of-timeout)
        $messages = $this->fetchNewMessages($conversationId, $userId, $lastMessageId);

        if ($messages->isNotEmpty()) {
            $this->markAsRead($conversationId, $userId);
        }

        return $messages;
    }

    /**
     * Accept a message request (instructor only).
     */
    public function acceptRequest(int $conversationId, int $instructorId): bool
    {
        return (bool) Conversation::where('id', $conversationId)
            ->where('instructor_id', $instructorId)
            ->where('is_accepted', false)
            ->update(['is_accepted' => true]);
    }

    /**
     * Decline a message request (instructor only).
     */
    public function declineRequest(int $conversationId, int $instructorId): bool
    {
        return (bool) Conversation::where('id', $conversationId)
            ->where('instructor_id', $instructorId)
            ->where('is_accepted', false)
            ->update(['is_closed' => true]);
    }

    /**
     * Mark all messages from the other party as read.
     */
    public function markAsRead(int $conversationId, int $userId): void
    {
        ChatMessage::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Get total unread count for a user (for badge display).
     */
    public function getUnreadCount(int $userId, string $role): int
    {
        $conversationIds = Conversation::query()
            ->where($role === 'student' ? 'student_id' : 'instructor_id', $userId)
            ->where('is_accepted', true)
            ->where('is_closed', false)
            ->pluck('id');

        if ($conversationIds->isEmpty()) {
            return 0;
        }

        return ChatMessage::whereIn('conversation_id', $conversationIds)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Get unread request count (for instructor badge).
     */
    public function getRequestCount(int $instructorId): int
    {
        return Conversation::where('instructor_id', $instructorId)
            ->where('is_accepted', false)
            ->where('is_closed', false)
            ->count();
    }

    // ── Private Helpers ──

    private function fetchNewMessages(int $conversationId, int $userId, int $lastMessageId): Collection
    {
        return ChatMessage::where('conversation_id', $conversationId)
            ->where('id', '>', $lastMessageId)
            ->with(['sender:id,name,profile_photo', 'attachments'])
            ->orderBy('id')
            ->get();
    }

    private function storeAttachments(ChatMessage $message, array $files): void
    {
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('chat-attachments/' . $message->conversation_id, 'public');

            ChatAttachment::create([
                'chat_message_id' => $message->id,
                'file_name'       => $file->getClientOriginalName(),
                'file_path'       => $path,
                'file_type'       => $file->getMimeType(),
                'file_size'       => $file->getSize(),
            ]);
        }
    }
}
