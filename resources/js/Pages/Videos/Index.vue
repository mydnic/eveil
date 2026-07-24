<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SortableTableHeader from '@/Components/SortableTableHeader.vue';
import ThumbnailGeneratorModal from '@/Components/ThumbnailGeneratorModal.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    connected: Boolean,
    channel: Object,
    videos: Array,
    error: String,
});

const localVideos = ref(props.videos);
const generatorOpen = ref(false);
const activeVideo = ref(null);

const search = ref('');
const dateFrom = ref('');
const dateTo = ref('');
const sorting = ref([]);

const hasFilters = computed(() => search.value || dateFrom.value || dateTo.value);

const filteredVideos = computed(() => {
    return localVideos.value.filter((video) => {
        const publishedDate = video.published_at.slice(0, 10);

        if (dateFrom.value && publishedDate < dateFrom.value) {
            return false;
        }

        if (dateTo.value && publishedDate > dateTo.value) {
            return false;
        }

        return true;
    });
});

function clearFilters() {
    search.value = '';
    dateFrom.value = '';
    dateTo.value = '';
}

function openGenerator(video) {
    activeVideo.value = video;
    generatorOpen.value = true;
}

function onThumbnailPublished(dataUrl) {
    const video = localVideos.value.find((v) => v.video_id === activeVideo.value.video_id);

    if (video) {
        video.thumbnail_url = dataUrl;
    }
}

const privacyColor = {
    public: 'success',
    unlisted: 'warning',
    private: 'neutral',
};

const columns = [
    { accessorKey: 'title', header: 'Video', meta: { class: { td: 'max-w-md' } } },
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

        <div v-else-if="localVideos.length === 0" class="py-24 text-center text-(--ui-text-muted)">
            No videos found on this channel.
        </div>

        <template v-else>
            <div class="mb-4 flex flex-wrap items-end gap-3">
                <UInput v-model="search" icon="i-lucide-search" placeholder="Search videos…" class="w-64" />

                <UFormField label="From">
                    <input
                        v-model="dateFrom"
                        type="date"
                        class="h-9 rounded-(--ui-radius) border border-(--ui-border) bg-(--ui-bg) px-2 text-sm"
                    />
                </UFormField>

                <UFormField label="To">
                    <input
                        v-model="dateTo"
                        type="date"
                        class="h-9 rounded-(--ui-radius) border border-(--ui-border) bg-(--ui-bg) px-2 text-sm"
                    />
                </UFormField>

                <UButton v-if="hasFilters" color="neutral" variant="ghost" icon="i-lucide-x" @click="clearFilters">
                    Clear
                </UButton>
            </div>

            <UTable
                v-model:sorting="sorting"
                v-model:global-filter="search"
                :data="filteredVideos"
                :columns="columns"
                class="shrink-0"
            >
                <template #title-cell="{ row }">
                    <div class="flex items-center gap-3 py-1">
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
                        <span class="line-clamp-2 min-w-0 font-medium">{{ row.original.title }}</span>
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

                <template #published_at-header="{ column }">
                    <SortableTableHeader :column="column" label="Date" />
                </template>

                <template #published_at-cell="{ row }">
                    <span class="text-sm text-nowrap text-(--ui-text-muted)">
                        {{ formatDate(row.original.published_at) }}
                    </span>
                </template>

                <template #view_count-header="{ column }">
                    <SortableTableHeader :column="column" label="Views" />
                </template>

                <template #view_count-cell="{ row }">
                    {{ formatNumber(row.original.view_count) }}
                </template>

                <template #comment_count-header="{ column }">
                    <SortableTableHeader :column="column" label="Comments" />
                </template>

                <template #comment_count-cell="{ row }">
                    {{ formatNumber(row.original.comment_count) }}
                </template>

                <template #like_count-header="{ column }">
                    <SortableTableHeader :column="column" label="Likes" />
                </template>

                <template #like_count-cell="{ row }">
                    {{ formatNumber(row.original.like_count) }}
                </template>

                <template #actions-cell="{ row }">
                    <div class="flex items-center justify-end gap-1">
                        <UTooltip text="Generate thumbnail">
                            <UButton
                                icon="i-lucide-image-plus"
                                color="neutral"
                                variant="ghost"
                                size="sm"
                                @click="openGenerator(row.original)"
                            />
                        </UTooltip>
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
        </template>

        <ThumbnailGeneratorModal
            v-model:open="generatorOpen"
            :video="activeVideo"
            @published="onThumbnailPublished"
        />
    </AuthenticatedLayout>
</template>
