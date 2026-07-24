<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

defineProps({
    connected: Boolean,
    channel: Object,
    videos: Array,
    error: String,
});

const privacyColor = {
    public: 'success',
    unlisted: 'warning',
    private: 'neutral',
};

const columns = [
    { accessorKey: 'title', header: 'Video' },
    { accessorKey: 'privacy_status', header: 'Visibility' },
    { accessorKey: 'published_at', header: 'Date' },
    { accessorKey: 'view_count', header: 'Views' },
    { accessorKey: 'comment_count', header: 'Comments' },
    { accessorKey: 'like_count', header: 'Likes' },
    { id: 'actions', header: '' },
];

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

function formatNumber(value) {
    if (value === null || value === undefined) {
        return '—';
    }

    return new Intl.NumberFormat(undefined, { notation: 'compact' }).format(value);
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

        <UTable v-else :data="videos" :columns="columns" class="shrink-0">
            <template #title-cell="{ row }">
                <div class="flex min-w-72 items-center gap-3 py-1">
                    <div class="relative w-32 shrink-0">
                        <img
                            :src="row.original.thumbnail_url"
                            :alt="row.original.title"
                            class="aspect-video w-full rounded-(--ui-radius) object-cover"
                        />
                        <span
                            class="absolute right-1 bottom-1 rounded bg-black/80 px-1 text-xs text-white"
                        >
                            {{ row.original.duration }}
                        </span>
                    </div>
                    <span class="line-clamp-2 font-medium">{{ row.original.title }}</span>
                </div>
            </template>

            <template #privacy_status-cell="{ row }">
                <UBadge
                    :color="privacyColor[row.original.privacy_status] ?? 'neutral'"
                    variant="subtle"
                    class="capitalize"
                >
                    {{ row.original.privacy_status }}
                </UBadge>
            </template>

            <template #published_at-cell="{ row }">
                <span class="text-sm text-nowrap text-(--ui-text-muted)">
                    {{ formatDate(row.original.published_at) }}
                </span>
            </template>

            <template #view_count-cell="{ row }">
                {{ formatNumber(row.original.view_count) }}
            </template>

            <template #comment_count-cell="{ row }">
                {{ formatNumber(row.original.comment_count) }}
            </template>

            <template #like_count-cell="{ row }">
                {{ formatNumber(row.original.like_count) }}
            </template>

            <template #actions-cell="{ row }">
                <div class="flex items-center justify-end gap-1">
                    <UTooltip text="Watch on YouTube">
                        <UButton
                            :to="`https://youtube.com/watch?v=${row.original.video_id}`"
                            target="_blank"
                            icon="i-lucide-external-link"
                            color="neutral"
                            variant="ghost"
                            size="sm"
                        />
                    </UTooltip>
                    <UTooltip text="Edit in YouTube Studio">
                        <UButton
                            :to="`https://studio.youtube.com/video/${row.original.video_id}/edit`"
                            target="_blank"
                            icon="i-lucide-square-pen"
                            color="neutral"
                            variant="ghost"
                            size="sm"
                        />
                    </UTooltip>
                </div>
            </template>
        </UTable>
    </AuthenticatedLayout>
</template>
