<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    open: Boolean,
    video: Object,
});

const emit = defineEmits(['update:open', 'published']);

const toast = useToast();

const step = ref('loading');
const errorMessage = ref(null);
const description = ref('');
const currentDescription = ref('');

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen && props.video) {
            generate();
        }
    },
);

function close() {
    emit('update:open', false);
}

function reportError(error, fallback) {
    errorMessage.value = error?.response?.data?.message ?? fallback;
    step.value = 'error';
}

async function generate() {
    step.value = 'loading';
    errorMessage.value = null;

    try {
        const { data } = await axios.post(route('videos.description.generate', props.video.video_id));

        description.value = data.description;
        currentDescription.value = data.current_description;
        step.value = 'edit';
    } catch (error) {
        reportError(error, "Couldn't generate a description.");
    }
}

async function publish() {
    step.value = 'publishing';

    try {
        await axios.post(route('videos.description.publish', props.video.video_id), {
            description: description.value,
        });

        toast.add({ title: 'Description updated on YouTube.', color: 'success' });
        emit('published', description.value);
        close();
    } catch (error) {
        reportError(error, "Couldn't update the description on YouTube.");
    }
}
</script>

<template>
    <UModal
        :open="open"
        :title="`Generate description — ${video?.title ?? ''}`"
        :ui="{ content: 'sm:max-w-2xl' }"
        @update:open="(value) => emit('update:open', value)"
    >
        <template #body>
            <div v-if="step === 'loading'" class="flex flex-col items-center gap-3 py-12">
                <UIcon name="i-lucide-loader-circle" class="size-8 animate-spin text-(--ui-text-muted)" />
                <p class="text-(--ui-text-muted)">Writing a description… this can take a minute or two with local models.</p>
            </div>

            <div v-else-if="step === 'error'" class="flex flex-col items-center gap-4 py-12 text-center">
                <UIcon name="i-lucide-triangle-alert" class="size-8 text-(--ui-error)" />
                <p class="text-(--ui-text-muted)">{{ errorMessage }}</p>
                <UButton variant="subtle" color="neutral" @click="generate">Try again</UButton>
            </div>

            <div v-else-if="step === 'edit' || step === 'publishing'" class="flex flex-col gap-4">
                <UTextarea v-model="description" :rows="10" autoresize class="w-full" />
                <div class="flex justify-between gap-2">
                    <UButton
                        variant="subtle"
                        color="neutral"
                        icon="i-lucide-refresh-cw"
                        :disabled="step === 'publishing'"
                        @click="generate"
                    >
                        Regenerate
                    </UButton>
                    <UButton icon="i-lucide-upload" :loading="step === 'publishing'" @click="publish">
                        Publish to YouTube
                    </UButton>
                </div>
            </div>
        </template>
    </UModal>
</template>
