<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Laravel\Ai\Models\ConversationMessage;

class ChatHistory
{
    /**
     * Convert stored conversation messages into the Vercel AI SDK's
     * UIMessage shape, for hydrating the frontend's useChat on first load.
     *
     * @param  Collection<int, ConversationMessage>  $messages
     * @return array<int, array{id: string, role: string, parts: array}>
     */
    public static function toUiMessages(Collection $messages): array
    {
        return $messages
            ->filter(fn (ConversationMessage $message) => in_array($message->role, ['user', 'assistant'], true))
            ->map(fn (ConversationMessage $message) => [
                'id' => (string) $message->id,
                'role' => $message->role,
                'parts' => [['type' => 'text', 'text' => (string) $message->content]],
            ])
            ->values()
            ->all();
    }
}
