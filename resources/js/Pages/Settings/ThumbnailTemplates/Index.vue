<script setup>
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    templates: Array,
});

function destroy(template) {
    if (confirm(`Delete template "${template.name}"?`)) {
        router.delete(route('settings.thumbnail-templates.destroy', template.id));
    }
}

function makeDefault(template) {
    router.post(route('settings.thumbnail-templates.default', template.id));
}
</script>

<template>
    <Head title="Thumbnail templates" />

    <SettingsLayout title="Thumbnail templates">
        <div class="mb-6 flex items-start justify-between gap-4">
            <p class="max-w-2xl text-(--ui-text-muted)">
                Create a template per game or series — e.g. one for boss fights, one for Battlefield. The
                matching template is picked automatically when generating a thumbnail, based on keywords.
            </p>
            <UButton :to="route('settings.thumbnail-templates.create')" icon="i-lucide-plus" class="shrink-0">
                New template
            </UButton>
        </div>

        <div class="flex flex-col gap-3">
            <UCard v-for="template in templates" :key="template.id">
                <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <Link
                                :href="route('settings.thumbnail-templates.edit', template.id)"
                                class="font-medium hover:underline"
                            >
                                {{ template.name }}
                            </Link>
                            <UBadge v-if="template.is_default" color="primary" variant="subtle">Default</UBadge>
                        </div>
                        <p class="mt-1 text-sm text-(--ui-text-muted)">
                            {{
                                template.game_keywords
                                    ? `Matches: ${template.game_keywords}`
                                    : 'No keywords — only used when picked manually'
                            }}
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-1">
                        <UTooltip v-if="!template.is_default" text="Set as default">
                            <UButton
                                icon="i-lucide-star"
                                color="neutral"
                                variant="ghost"
                                size="sm"
                                @click="makeDefault(template)"
                            />
                        </UTooltip>
                        <UTooltip text="Edit">
                            <UButton
                                :to="route('settings.thumbnail-templates.edit', template.id)"
                                icon="i-lucide-pencil"
                                color="neutral"
                                variant="ghost"
                                size="sm"
                            />
                        </UTooltip>
                        <UTooltip :text="template.is_default ? 'Set another template as default to delete this one' : 'Delete'">
                            <UButton
                                :disabled="template.is_default"
                                icon="i-lucide-trash-2"
                                color="error"
                                variant="ghost"
                                size="sm"
                                @click="destroy(template)"
                            />
                        </UTooltip>
                    </div>
                </div>
            </UCard>
        </div>
    </SettingsLayout>
</template>
