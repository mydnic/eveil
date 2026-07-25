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
const game = ref('');
const boss = ref('');
const searchQuery = ref('');
const searching = ref(false);
const candidates = ref([]);
const selectedUrl = ref(null);
const previewDataUrl = ref(null);
const templates = ref([]);
const templateId = ref(null);

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen && props.video) {
            fetchCandidates();
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

async function fetchCandidates() {
    step.value = 'loading';
    errorMessage.value = null;
    candidates.value = [];
    selectedUrl.value = null;
    previewDataUrl.value = null;

    try {
        const { data } = await axios.post(route('videos.thumbnail.candidates', props.video.video_id), {
            title: props.video.title,
        });

        game.value = data.game;
        boss.value = data.boss;
        searchQuery.value = data.search_query;
        candidates.value = data.candidates;
        templates.value = data.templates;
        templateId.value = data.template_id;
        step.value = 'select';
    } catch (error) {
        reportError(error, "Couldn't find candidate images.");
    }
}

async function runSearch() {
    const query = searchQuery.value.trim();

    if (!query) {
        return;
    }

    searching.value = true;

    try {
        const { data } = await axios.post(route('thumbnail.search'), { query });
        candidates.value = data.candidates;
    } catch (error) {
        toast.add({
            title: "Couldn't search for images.",
            description: error?.response?.data?.message,
            color: 'error',
        });
    } finally {
        searching.value = false;
    }
}

async function selectCandidate(candidate) {
    selectedUrl.value = candidate.url;
    await generatePreview();
}

function formatResolution(candidate) {
    return candidate.width && candidate.height ? `${candidate.width}×${candidate.height}` : null;
}

async function generatePreview() {
    step.value = 'previewing';

    try {
        const { data } = await axios.post(route('thumbnail.preview'), {
            image_url: selectedUrl.value,
            game: game.value,
            boss: boss.value,
            template_id: templateId.value,
        });

        previewDataUrl.value = data.data_url;
        step.value = 'preview';
    } catch (error) {
        reportError(error, "Couldn't generate a preview for that image.");
    }
}

watch(templateId, () => {
    if (selectedUrl.value && (step.value === 'preview' || step.value === 'previewing')) {
        generatePreview();
    }
});

async function publish() {
    step.value = 'publishing';

    try {
        const { data } = await axios.post(route('videos.thumbnail.publish', props.video.video_id), {
            image_url: selectedUrl.value,
            game: game.value,
            boss: boss.value,
            template_id: templateId.value,
        });

        toast.add({ title: 'Thumbnail updated on YouTube.', color: 'success' });
        emit('published', data.data_url);
        close();
    } catch (error) {
        reportError(error, "Couldn't upload the thumbnail to YouTube.");
    }
}
</script>

<template>
    <UModal
        :open="open"
        :title="`Generate thumbnail — ${video?.title ?? ''}`"
        :ui="{ content: 'sm:max-w-6xl', body: 'overflow-y-auto' }"
        @update:open="(value) => emit('update:open', value)"
    >
        <template #body>
            <div v-if="step === 'loading'" class="flex flex-col items-center gap-3 py-12">
                <UIcon name="i-lucide-loader-circle" class="size-8 animate-spin text-(--ui-text-muted)" />
                <p class="text-(--ui-text-muted)">Searching for images…</p>
            </div>

            <div v-else-if="step === 'error'" class="flex flex-col items-center gap-4 py-12 text-center">
                <UIcon name="i-lucide-triangle-alert" class="size-8 text-(--ui-error)" />
                <p class="text-(--ui-text-muted)">{{ errorMessage }}</p>
                <UButton variant="subtle" color="neutral" @click="fetchCandidates">Try again</UButton>
            </div>

            <template v-else>
                <UFormField v-if="templates.length > 1" label="Thumbnail template" class="mb-4">
                    <USelect
                        v-model="templateId"
                        :items="templates.map((t) => ({ label: t.name, value: t.id }))"
                        class="w-56"
                    />
                </UFormField>

                <div v-if="step === 'select'">
                    <p class="mb-2 text-sm text-(--ui-text-muted)">
                        Looking for <span class="font-medium">{{ boss }}</span> from
                        <span class="font-medium">{{ game }}</span>. Not finding a good one? Tweak the search:
                    </p>
                    <div class="mb-4 flex gap-2">
                        <UInput
                            v-model="searchQuery"
                            placeholder="Search term…"
                            class="flex-1"
                            @keyup.enter="runSearch"
                        />
                        <UButton icon="i-lucide-search" :loading="searching" @click="runSearch">Search</UButton>
                    </div>

                    <div v-if="searching" class="flex items-center justify-center py-24">
                        <UIcon name="i-lucide-loader-circle" class="size-8 animate-spin text-(--ui-text-muted)" />
                    </div>

                    <div v-else-if="candidates.length === 0" class="py-24 text-center text-(--ui-text-muted)">
                        No images found for this search. Try a different term.
                    </div>

                    <template v-else>
                        <p class="mb-3 text-xs text-(--ui-text-dimmed)">
                            {{ candidates.length }} results, best resolution first
                        </p>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <button
                                v-for="candidate in candidates"
                                :key="candidate.url"
                                type="button"
                                class="relative aspect-video overflow-hidden rounded-(--ui-radius) bg-(--ui-bg-elevated) ring-2 ring-transparent transition hover:ring-(--ui-primary)"
                                @click="selectCandidate(candidate)"
                            >
                                <img :src="candidate.url" class="h-full w-full object-cover" loading="lazy" />
                                <span
                                    v-if="formatResolution(candidate)"
                                    class="absolute right-1.5 bottom-1.5 rounded bg-black/80 px-1.5 py-0.5 text-xs text-white"
                                >
                                    {{ formatResolution(candidate) }}
                                </span>
                            </button>
                        </div>
                    </template>
                </div>

                <div v-else-if="step === 'previewing'" class="flex flex-col items-center gap-3 py-12">
                    <UIcon name="i-lucide-loader-circle" class="size-8 animate-spin text-(--ui-text-muted)" />
                    <p class="text-(--ui-text-muted)">Generating preview…</p>
                </div>

                <div v-else-if="step === 'preview' || step === 'publishing'" class="flex flex-col items-center gap-4">
                    <img :src="previewDataUrl" class="aspect-video w-full rounded-(--ui-radius) object-cover" />
                    <div class="flex gap-2">
                        <UButton
                            variant="subtle"
                            color="neutral"
                            icon="i-lucide-arrow-left"
                            :disabled="step === 'publishing'"
                            @click="step = 'select'"
                        >
                            Choose another image
                        </UButton>
                        <UButton icon="i-lucide-upload" :loading="step === 'publishing'" @click="publish">
                            Publish to YouTube
                        </UButton>
                    </div>
                </div>
            </template>
        </template>
    </UModal>
</template>
