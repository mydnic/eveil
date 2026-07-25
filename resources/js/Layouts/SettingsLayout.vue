<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    title: { type: String, required: true },
});

const navItems = [
    {
        label: 'Thumbnail template',
        icon: 'i-lucide-image',
        to: route('settings.thumbnail-template.edit'),
        active: route().current('settings.thumbnail-template.edit'),
    },
    {
        label: 'Channel profile',
        icon: 'i-lucide-badge-info',
        to: route('settings.channel-profile.edit'),
        active: route().current('settings.channel-profile.edit'),
    },
];
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold">{{ title }}</h2>
        </template>

        <div class="flex flex-col gap-8 lg:flex-row">
            <UNavigationMenu :items="navItems" orientation="vertical" class="lg:w-56 lg:shrink-0" />

            <div class="min-w-0 flex-1">
                <slot />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
