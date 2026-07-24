<script setup>
import { Comark } from '@comark/vue';
import { useChat } from '@ai-sdk/vue';
import { DefaultChatTransport } from 'ai';
import { ref } from 'vue';

const props = defineProps({
    endpoint: { type: String, required: true },
    initialMessages: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Ask something…' },
});

function xsrfToken() {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

const { messages, sendMessage, status } = useChat({
    messages: props.initialMessages,
    transport: new DefaultChatTransport({
        api: props.endpoint,
        headers: () => ({ 'X-XSRF-TOKEN': xsrfToken() }),
    }),
});

const input = ref('');

function onSubmit() {
    const text = input.value.trim();

    if (!text) {
        return;
    }

    sendMessage({ text });
    input.value = '';
}
</script>

<template>
    <div class="flex h-full flex-col">
        <UChatMessages :messages="messages" :status="status" class="flex-1" should-scroll-to-bottom should-auto-scroll>
            <template #content="{ message }">
                <template v-for="(part, index) in message.parts" :key="`${message.id}-${index}`">
                    <Comark v-if="part.type === 'text'" :streaming="status === 'streaming'" caret>
                        {{ part.text }}
                    </Comark>
                </template>
            </template>
        </UChatMessages>

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
