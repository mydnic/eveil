<script setup>
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import ThumbnailTextLayerFields from '@/Components/ThumbnailTextLayerFields.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    template: { type: Object, default: null },
    fonts: Array,
});

const isEditing = !!props.template;

function defaultTexts() {
    return [
        {
            kind: 'game',
            content: null,
            font: 'oswald',
            font_color: '#FF3B30',
            font_size: 48,
            x_percent: 50,
            y_percent: 75,
            align: 'center',
            rotation: 0,
            stroke_color: '#000000',
            stroke_width: 3,
            uppercase: true,
        },
        {
            kind: 'boss',
            content: null,
            font: 'anton',
            font_color: '#FFFFFF',
            font_size: 90,
            x_percent: 50,
            y_percent: 92,
            align: 'center',
            rotation: 0,
            stroke_color: '#000000',
            stroke_width: 6,
            uppercase: true,
        },
    ];
}

function newFixedText() {
    return {
        kind: 'fixed',
        content: '',
        font: 'oswald',
        font_color: '#FFFFFF',
        font_size: 28,
        x_percent: 50,
        y_percent: 15,
        align: 'center',
        rotation: 0,
        stroke_color: '#000000',
        stroke_width: 4,
        uppercase: true,
    };
}

const form = useForm({
    name: props.template?.name ?? '',
    is_default: props.template?.is_default ?? false,
    game_keywords: props.template?.game_keywords ?? '',
    gradient_height_percent: props.template?.gradient_height_percent ?? 55,
    gradient_position: props.template?.gradient_position ?? 'bottom',
    texts: props.template?.texts?.length ? props.template.texts : defaultTexts(),
});

const gameLayer = computed(() => form.texts.find((t) => t.kind === 'game'));
const bossLayer = computed(() => form.texts.find((t) => t.kind === 'boss'));
const fixedLayers = computed(() => form.texts.filter((t) => t.kind === 'fixed'));

function addFixedText() {
    form.texts.push(newFixedText());
}

function removeFixedText(layer) {
    form.texts = form.texts.filter((t) => t !== layer);
}

const pageTitle = computed(() => (isEditing ? `Edit template — ${props.template.name}` : 'New thumbnail template'));
const hasTextErrors = computed(() => Object.keys(form.errors).some((key) => key.startsWith('texts.')));

const previewUrl = ref(null);
const previewing = ref(false);
let debounceTimer = null;

async function refreshPreview() {
    previewing.value = true;

    try {
        const { data } = await axios.post(route('settings.thumbnail-templates.preview'), form.data());
        previewUrl.value = data.data_url;
    } finally {
        previewing.value = false;
    }
}

watch(
    () => form.data(),
    () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(refreshPreview, 400);
    },
    { deep: true, immediate: true },
);

function save() {
    if (isEditing) {
        form.put(route('settings.thumbnail-templates.update', props.template.id), { preserveScroll: true });
    } else {
        form.post(route('settings.thumbnail-templates.store'));
    }
}
</script>

<template>
    <Head :title="pageTitle" />

    <SettingsLayout :title="pageTitle">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
            <form class="flex flex-col gap-6" @submit.prevent="save">
                <UCard>
                    <template #header>
                        <h3 class="font-medium">Template</h3>
                    </template>

                    <div class="flex flex-col gap-4">
                        <UFormField label="Name" :error="form.errors.name">
                            <UInput v-model="form.name" class="w-full" placeholder="e.g. Battlefield" />
                        </UFormField>

                        <UFormField
                            label="Game keywords"
                            description="Comma-separated. This template is auto-selected when the video's game name contains one of these — e.g. &quot;battlefield, bf6&quot;."
                            :error="form.errors.game_keywords"
                        >
                            <UInput v-model="form.game_keywords" class="w-full" placeholder="battlefield, bf6" />
                        </UFormField>

                        <div class="flex flex-wrap gap-4">
                            <UFormField label="Gradient height (%)" :error="form.errors.gradient_height_percent">
                                <UInput
                                    v-model.number="form.gradient_height_percent"
                                    type="number"
                                    min="0"
                                    max="100"
                                    class="w-24"
                                />
                            </UFormField>

                            <UFormField label="Gradient position" :error="form.errors.gradient_position">
                                <USelect
                                    v-model="form.gradient_position"
                                    :items="[
                                        { label: 'Bottom', value: 'bottom' },
                                        { label: 'Top', value: 'top' },
                                        { label: 'Top and bottom', value: 'both' },
                                    ]"
                                    class="w-40"
                                />
                            </UFormField>
                        </div>

                        <UCheckbox
                            v-model="form.is_default"
                            label="Use as the default template"
                            description="Applied whenever no other template's keywords match."
                        />
                    </div>
                </UCard>

                <UAlert
                    v-if="hasTextErrors"
                    color="error"
                    variant="subtle"
                    icon="i-lucide-triangle-alert"
                    title="Some text layer fields are invalid — check the values below."
                />

                <ThumbnailTextLayerFields v-if="gameLayer" :layer="gameLayer" :fonts="fonts" label="Game name" />
                <ThumbnailTextLayerFields v-if="bossLayer" :layer="bossLayer" :fonts="fonts" label="Boss name" />

                <ThumbnailTextLayerFields
                    v-for="layer in fixedLayers"
                    :key="layer"
                    :layer="layer"
                    :fonts="fonts"
                    removable
                    @remove="removeFixedText(layer)"
                />

                <UButton
                    variant="subtle"
                    color="neutral"
                    icon="i-lucide-plus"
                    class="self-start"
                    @click="addFixedText"
                >
                    Add fixed text
                </UButton>

                <div class="flex items-center gap-3">
                    <UButton type="submit" :loading="form.processing">
                        {{ isEditing ? 'Save template' : 'Create template' }}
                    </UButton>
                    <UButton :to="route('settings.thumbnail-templates.index')" color="neutral" variant="ghost">
                        Cancel
                    </UButton>
                </div>
            </form>

            <div class="lg:sticky lg:top-8 lg:self-start">
                <p class="mb-2 text-sm text-(--ui-text-muted)">Live preview</p>
                <div class="relative aspect-video overflow-hidden rounded-(--ui-radius) bg-(--ui-bg-elevated)">
                    <img v-if="previewUrl" :src="previewUrl" class="h-full w-full object-cover" />
                    <div
                        v-if="previewing"
                        class="absolute inset-0 flex items-center justify-center bg-black/20"
                    >
                        <UIcon name="i-lucide-loader-circle" class="size-6 animate-spin text-white" />
                    </div>
                </div>
            </div>
        </div>
    </SettingsLayout>
</template>
