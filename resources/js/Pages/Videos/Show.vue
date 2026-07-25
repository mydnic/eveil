<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ChatSidePanel from '@/Components/ChatSidePanel.vue';
import DescriptionGeneratorModal from '@/Components/DescriptionGeneratorModal.vue';
import LineChart from '@/Components/LineChart.vue';
import ThumbnailGeneratorModal from '@/Components/ThumbnailGeneratorModal.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    video: Object,
    dailyMetrics: Array,
    analyticsError: String,
    chatMessages: Array,
});

const localVideo = ref(props.video);
const thumbnailGeneratorOpen = ref(false);
const descriptionGeneratorOpen = ref(false);

function onThumbnailPublished(dataUrl) {
    localVideo.value.thumbnail_url = dataUrl;
}

function onDescriptionPublished(description) {
    localVideo.value.description = description;
}

const viewsSeries = computed(() => props.dailyMetrics.map((d) => ({ date: d.date, value: d.views })));
const likesSeries = computed(() => props.dailyMetrics.map((d) => ({ date: d.date, value: d.likes })));

function formatNumber(value) {
    if (value === null || value === undefined) {
        return '—';
    }

    return new Intl.NumberFormat(undefined, { notation: 'compact' }).format(value);
}

function formatDate(value) {
    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <Head :title="localVideo.title" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="line-clamp-1 text-xl font-semibold">{{ localVideo.title }}</h2>
        </template>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="flex flex-col gap-6 lg:col-span-2">
                <div class="flex flex-col gap-4 sm:flex-row">
                    <div class="relative w-full shrink-0 sm:w-64">
                        <img
                            :src="localVideo.thumbnail_url"
                            :alt="localVideo.title"
                            class="aspect-video w-full rounded-(--ui-radius) object-cover"
                        />
                        <UButton
                            icon="i-lucide-image-plus"
                            color="neutral"
                            size="sm"
                            class="absolute right-2 bottom-2 backdrop-blur"
                            @click="thumbnailGeneratorOpen = true"
                        >
                            Edit thumbnail
                        </UButton>
                    </div>
                    <div class="grid flex-1 grid-cols-2 gap-4">
                        <UCard>
                            <p class="text-sm text-(--ui-text-muted)">Views</p>
                            <p class="text-2xl font-semibold">{{ formatNumber(localVideo.view_count) }}</p>
                        </UCard>
                        <UCard>
                            <p class="text-sm text-(--ui-text-muted)">Likes</p>
                            <p class="text-2xl font-semibold">{{ formatNumber(localVideo.like_count) }}</p>
                        </UCard>
                        <UCard>
                            <p class="text-sm text-(--ui-text-muted)">Comments</p>
                            <p class="text-2xl font-semibold">{{ formatNumber(localVideo.comment_count) }}</p>
                        </UCard>
                        <UCard>
                            <p class="text-sm text-(--ui-text-muted)">Published</p>
                            <p class="text-2xl font-semibold">{{ formatDate(localVideo.published_at) }}</p>
                        </UCard>
                    </div>
                </div>

                <UCard>
                    <template #header>
                        <div class="flex items-center justify-between">
                            <h3 class="font-medium">Description</h3>
                            <UButton
                                icon="i-lucide-file-text"
                                color="neutral"
                                variant="subtle"
                                size="sm"
                                @click="descriptionGeneratorOpen = true"
                            >
                                Generate description
                            </UButton>
                        </div>
                    </template>
                    <p v-if="localVideo.description" class="text-sm whitespace-pre-wrap text-(--ui-text-muted)">
                        {{ localVideo.description }}
                    </p>
                    <p v-else class="text-sm text-(--ui-text-dimmed)">No description yet.</p>
                </UCard>

                <UAlert
                    v-if="analyticsError"
                    color="warning"
                    variant="subtle"
                    icon="i-lucide-triangle-alert"
                    :description="analyticsError"
                />

                <template v-else>
                    <UCard>
                        <template #header>
                            <h3 class="font-medium">Views — last 28 days</h3>
                        </template>
                        <LineChart :data="viewsSeries" />
                    </UCard>

                    <UCard>
                        <template #header>
                            <h3 class="font-medium">Likes — last 28 days</h3>
                        </template>
                        <LineChart :data="likesSeries" color="var(--ui-secondary)" />
                    </UCard>
                </template>
            </div>

            <div class="h-[calc(100vh-12rem)] lg:sticky lg:top-8">
                <UCard class="flex h-full flex-col" :ui="{ body: 'flex min-h-0 flex-1 flex-col' }">
                    <template #header>
                        <h3 class="font-medium">Ask about this video</h3>
                    </template>
                    <ChatSidePanel
                        :endpoint="route('videos.chat', localVideo.video_id)"
                        :initial-messages="chatMessages"
                        :description-publish-endpoint="route('videos.description.publish', localVideo.video_id)"
                        placeholder="Ask for ideas to improve this video…"
                        @description-published="onDescriptionPublished"
                    />
                </UCard>
            </div>
        </div>

        <ThumbnailGeneratorModal
            v-model:open="thumbnailGeneratorOpen"
            :video="localVideo"
            @published="onThumbnailPublished"
        />

        <DescriptionGeneratorModal
            v-model:open="descriptionGeneratorOpen"
            :video="localVideo"
            @published="onDescriptionPublished"
        />
    </AuthenticatedLayout>
</template>
