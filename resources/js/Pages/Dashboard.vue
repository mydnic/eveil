<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ChatSidePanel from '@/Components/ChatSidePanel.vue';
import LineChart from '@/Components/LineChart.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    connected: Boolean,
    channel: Object,
    dailyMetrics: Array,
    analyticsError: String,
    chatMessages: Array,
});

const viewsSeries = computed(() => (props.dailyMetrics ?? []).map((d) => ({ date: d.date, value: d.views })));
const likesSeries = computed(() => (props.dailyMetrics ?? []).map((d) => ({ date: d.date, value: d.likes })));

function formatNumber(value) {
    if (value === null || value === undefined) {
        return '—';
    }

    return new Intl.NumberFormat(undefined, { notation: 'compact' }).format(value);
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold">Dashboard</h2>
        </template>

        <div v-if="!connected" class="flex flex-col items-center gap-4 py-24 text-center">
            <UIcon name="i-simple-icons-youtube" class="size-12 text-(--ui-text-dimmed)" />
            <div>
                <h3 class="text-lg font-medium">Connect your YouTube channel</h3>
                <p class="text-(--ui-text-muted)">Connect your channel to see stats and chat with the AI about it.</p>
            </div>
            <UButton :to="route('youtube.connect')" icon="i-simple-icons-youtube" size="lg">
                Connect YouTube
            </UButton>
        </div>

        <div v-else class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="flex flex-col gap-6 lg:col-span-2">
                <div v-if="channel" class="flex items-center gap-4">
                    <UAvatar :src="channel.thumbnail_url" :alt="channel.title" size="xl" />
                    <div>
                        <h3 class="text-lg font-semibold">{{ channel.title }}</h3>
                        <p class="text-sm text-(--ui-text-muted)">
                            {{ formatNumber(channel.subscriber_count) }} subscribers ·
                            {{ formatNumber(channel.video_count) }} videos ·
                            {{ formatNumber(channel.view_count) }} lifetime views
                        </p>
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
                        <h3 class="font-medium">Ask about your channel</h3>
                    </template>
                    <ChatSidePanel
                        :endpoint="route('chat')"
                        :initial-messages="chatMessages"
                        placeholder="Ask about your channel's performance…"
                    />
                </UCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
