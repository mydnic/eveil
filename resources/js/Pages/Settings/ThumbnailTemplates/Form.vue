<script setup>
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    template: { type: Object, default: null },
    fonts: Array,
});

const isEditing = !!props.template;

const form = useForm({
    name: props.template?.name ?? '',
    is_default: props.template?.is_default ?? false,
    game_keywords: props.template?.game_keywords ?? '',
    game_font: props.template?.game_font ?? 'oswald',
    game_font_color: props.template?.game_font_color ?? '#FF3B30',
    boss_font: props.template?.boss_font ?? 'anton',
    boss_font_color: props.template?.boss_font_color ?? '#FFFFFF',
    stroke_color: props.template?.stroke_color ?? '#000000',
    stroke_width: props.template?.stroke_width ?? 6,
    gradient_height_percent: props.template?.gradient_height_percent ?? 55,
});

const pageTitle = computed(() => (isEditing ? `Edit template — ${props.template.name}` : 'New thumbnail template'));

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

                        <UCheckbox
                            v-model="form.is_default"
                            label="Use as the default template"
                            description="Applied whenever no other template's keywords match."
                        />
                    </div>
                </UCard>

                <UCard>
                    <template #header>
                        <h3 class="font-medium">Game name</h3>
                    </template>

                    <div class="flex flex-wrap gap-4">
                        <UFormField label="Font" :error="form.errors.game_font">
                            <USelect v-model="form.game_font" :items="fonts" class="w-48" />
                        </UFormField>

                        <UFormField label="Color" :error="form.errors.game_font_color">
                            <input
                                v-model="form.game_font_color"
                                type="color"
                                class="h-9 w-16 cursor-pointer rounded-(--ui-radius) border border-(--ui-border) bg-transparent p-1"
                            />
                        </UFormField>
                    </div>
                </UCard>

                <UCard>
                    <template #header>
                        <h3 class="font-medium">Boss name</h3>
                    </template>

                    <div class="flex flex-wrap gap-4">
                        <UFormField label="Font" :error="form.errors.boss_font">
                            <USelect v-model="form.boss_font" :items="fonts" class="w-48" />
                        </UFormField>

                        <UFormField label="Color" :error="form.errors.boss_font_color">
                            <input
                                v-model="form.boss_font_color"
                                type="color"
                                class="h-9 w-16 cursor-pointer rounded-(--ui-radius) border border-(--ui-border) bg-transparent p-1"
                            />
                        </UFormField>
                    </div>
                </UCard>

                <UCard>
                    <template #header>
                        <h3 class="font-medium">Stroke &amp; gradient</h3>
                    </template>

                    <div class="flex flex-wrap gap-4">
                        <UFormField label="Stroke color" :error="form.errors.stroke_color">
                            <input
                                v-model="form.stroke_color"
                                type="color"
                                class="h-9 w-16 cursor-pointer rounded-(--ui-radius) border border-(--ui-border) bg-transparent p-1"
                            />
                        </UFormField>

                        <UFormField label="Stroke width" :error="form.errors.stroke_width">
                            <UInput v-model.number="form.stroke_width" type="number" min="0" max="20" class="w-24" />
                        </UFormField>

                        <UFormField label="Gradient height (%)" :error="form.errors.gradient_height_percent">
                            <UInput
                                v-model.number="form.gradient_height_percent"
                                type="number"
                                min="0"
                                max="100"
                                class="w-24"
                            />
                        </UFormField>
                    </div>
                </UCard>

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
