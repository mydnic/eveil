<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ChatSidePanel from '@/Components/ChatSidePanel.vue';
import LineChart from '@/Components/LineChart.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    video: Object,
    dailyMetrics: Array,
    analyticsError: String,
    chatMessages: Array,
});

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
    <Head :title="video.title" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="line-clamp-1 text-xl font-semibold">{{ video.title }}</h2>
        </template>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="flex flex-col gap-6 lg:col-span-2">
                <div class="flex flex-col gap-4 sm:flex-row">
                    <img
                        :src="video.thumbnail_url"
                        :alt="video.title"
                        class="aspect-video w-full shrink-0 rounded-(--ui-radius) object-cover sm:w-64"
                    />
                    <div class="grid flex-1 grid-cols-2 gap-4">
                        <UCard>
                            <p class="text-sm text-(--ui-text-muted)">Views</p>
                            <p class="text-2xl font-semibold">{{ formatNumber(video.view_count) }}</p>
                        </UCard>
                        <UCard>
                            <p class="text-sm text-(--ui-text-muted)">Likes</p>
                            <p class="text-2xl font-semibold">{{ formatNumber(video.like_count) }}</p>
                        </UCard>
                        <UCard>
                            <p class="text-sm text-(--ui-text-muted)">Comments</p>
                            <p class="text-2xl font-semibold">{{ formatNumber(video.comment_count) }}</p>
                        </UCard>
                        <UCard>
                            <p class="text-sm text-(--ui-text-muted)">Published</p>
                            <p class="text-2xl font-semibold">{{ formatDate(video.published_at) }}</p>
                        </UCard>
                    </div>
                </div>

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
                        :endpoint="route('videos.chat', video.video_id)"
                        :initial-messages="chatMessages"
                        placeholder="Ask for ideas to improve this video…"
                    />
                </UCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
