<script setup>
import { Comark } from '@comark/vue';
import { useChat } from '@ai-sdk/vue';
import { DefaultChatTransport } from 'ai';
import { ref } from 'vue';

const props = defineProps({
    endpoint: { type: String, required: true },
    initialMessages: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Ask something…' },
    // When set, "data-description-suggestion" parts render as a card with a
    // "Publish to YouTube" button that posts here instead of plain text.
    descriptionPublishEndpoint: { type: String, default: null },
});

const emit = defineEmits(['description-published']);

const toast = useToast();

function xsrfToken() {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

const { messages, sendMessage, status, error } = useChat({
    messages: props.initialMessages,
    transport: new DefaultChatTransport({
        api: props.endpoint,
        headers: { 'X-XSRF-TOKEN': xsrfToken() },
        // The backend replays conversation history from the database (see
        // ChannelAssistant/VideoAssistant::messages()), so only the new
        // message needs to go over the wire rather than the full thread.
        prepareSendMessagesRequest: ({ messages: allMessages }) => ({
            body: { messages: allMessages.slice(-1) },
        }),
    }),
});

const input = ref('');

function onSubmit() {
    const text = input.value.trim();

    if (!text) {
        return;
    }

    input.value = '';
    sendMessage({ text });
}

const publishingSuggestionId = ref(null);
const publishedSuggestionIds = ref(new Set());

async function publishDescriptionSuggestion(part) {
    publishingSuggestionId.value = part.id;

    try {
        await axios.post(props.descriptionPublishEndpoint, { description: part.data.description });

        publishedSuggestionIds.value = new Set(publishedSuggestionIds.value).add(part.id);
        toast.add({ title: 'Description updated on YouTube.', color: 'success' });
        emit('description-published', part.data.description);
    } catch (err) {
        toast.add({
            title: "Couldn't update the description.",
            description: err?.response?.data?.message,
            color: 'error',
        });
    } finally {
        publishingSuggestionId.value = null;
    }
}
</script>

<template>
    <div class="flex h-full flex-col">
        <UChatMessages
            :messages="messages"
            :status="status"
            class="min-h-0 flex-1 overflow-y-auto"
            should-scroll-to-bottom
            should-auto-scroll
        >
            <template #content="{ message }">
                <template v-for="(part, index) in message.parts" :key="`${message.id}-${index}`">
                    <Suspense v-if="part.type === 'text'">
                        <Comark :markdown="part.text" :streaming="status === 'streaming'" caret />
                    </Suspense>

                    <UCard v-else-if="part.type === 'data-description-suggestion'" class="mt-2">
                        <template #header>
                            <p class="text-sm font-medium">Suggested description</p>
                        </template>

                        <p class="text-sm whitespace-pre-wrap">{{ part.data.description }}</p>

                        <div v-if="descriptionPublishEndpoint" class="mt-3 flex justify-end">
                            <UBadge
                                v-if="publishedSuggestionIds.has(part.id)"
                                color="success"
                                variant="subtle"
                                icon="i-lucide-check"
                            >
                                Published to YouTube
                            </UBadge>
                            <UButton
                                v-else
                                size="sm"
                                icon="i-lucide-upload"
                                :loading="publishingSuggestionId === part.id"
                                @click="publishDescriptionSuggestion(part)"
                            >
                                Publish to YouTube
                            </UButton>
                        </div>
                    </UCard>
                </template>
            </template>
        </UChatMessages>

        <UAlert
            v-if="error"
            color="error"
            variant="subtle"
            icon="i-lucide-triangle-alert"
            :title="error.message || 'Something went wrong.'"
            class="mb-2"
        />

        <UChatPrompt
            v-model="input"
            :placeholder="placeholder"
            :disabled="status === 'streaming'"
            class="mt-2"
            @submit="onSubmit"
        >
            <template #footer>
                <UChatPromptSubmit :status="status" />
            </template>
        </UChatPrompt>
    </div>
</template>
