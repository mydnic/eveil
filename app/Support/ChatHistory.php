<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Laravel\Ai\Messages\Message;
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

    /**
     * Convert stored conversation messages into plain text-only Message
     * objects for replaying as context to the model, dropping tool_calls/
     * tool_results. Resending a stored tool call currently 400s against our
     * LiteLLM/llama.cpp gateway ("Cannot determine type of 'item'") on the
     * next turn, even though the same tool call succeeds live — so we keep
     * the conversational memory but strip the tool metadata.
     *
     * @param  Collection<int, ConversationMessage>  $messages
     * @return array<int, Message>
     */
    public static function toPlainMessages(Collection $messages): array
    {
        return $messages
            ->filter(fn (ConversationMessage $message) => in_array($message->role, ['user', 'assistant'], true))
            ->map(fn (ConversationMessage $message) => new Message($message->role, (string) $message->content))
            ->values()
            ->all();
    }
}
