<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

defineProps({
    connected: Boolean,
    channel: Object,
    videos: Array,
    error: String,
});

function disconnect() {
    if (confirm('Disconnect this YouTube channel?')) {
        router.delete(route('youtube.disconnect'));
    }
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
    <Head title="Videos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold">Videos</h2>

                <div v-if="connected" class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <UAvatar :src="channel.thumbnail_url" :alt="channel.title" size="sm" />
                        <span class="text-sm text-(--ui-text-muted)">{{ channel.title }}</span>
                    </div>
                    <UButton color="neutral" variant="ghost" icon="i-lucide-unlink" @click="disconnect">
                        Disconnect
                    </UButton>
                </div>
            </div>
        </template>

        <UAlert
            v-if="error"
            color="error"
            variant="subtle"
            icon="i-lucide-triangle-alert"
            title="Couldn't load videos"
            :description="error"
            class="mb-6"
        />

        <div v-if="!connected" class="flex flex-col items-center gap-4 py-24 text-center">
            <UIcon name="i-simple-icons-youtube" class="size-12 text-(--ui-text-dimmed)" />
            <div>
                <h3 class="text-lg font-medium">Connect your YouTube channel</h3>
                <p class="text-(--ui-text-muted)">
                    Eveil needs access to your channel to list videos and update thumbnails.
                </p>
            </div>
            <UButton :to="route('youtube.connect')" icon="i-simple-icons-youtube" size="lg">
                Connect YouTube
            </UButton>
        </div>

        <div v-else-if="videos.length === 0" class="py-24 text-center text-(--ui-text-muted)">
            No videos found on this channel.
        </div>

        <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <UCard v-for="video in videos" :key="video.video_id">
                <img
                    :src="video.thumbnail_url"
                    :alt="video.title"
                    class="mb-3 aspect-video w-full rounded-(--ui-radius) object-cover"
                />
                <h3 class="line-clamp-2 font-medium">{{ video.title }}</h3>
                <p class="mt-1 text-sm text-(--ui-text-muted)">{{ formatDate(video.published_at) }}</p>
            </UCard>
        </div>
    </AuthenticatedLayout>
</template>
