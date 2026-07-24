<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import FlashToaster from '@/Components/FlashToaster.vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const page = usePage();

const navItems = [
    {
        label: 'Dashboard',
        to: route('dashboard'),
        active: route().current('dashboard'),
    },
    {
        label: 'Videos',
        to: route('videos.index'),
        active: route().current('videos.index'),
    },
];

const userMenuItems = [
    [
        {
            label: page.props.auth.user.name,
            avatar: { icon: 'i-lucide-user' },
            type: 'label',
        },
    ],
    [
        {
            label: 'Profile',
            icon: 'i-lucide-user-cog',
            to: route('profile.edit'),
        },
        {
            label: 'Thumbnail template',
            icon: 'i-lucide-image',
            to: route('settings.thumbnail-template.edit'),
        },
        {
            label: 'Channel profile',
            icon: 'i-lucide-badge-info',
            to: route('settings.channel-profile.edit'),
        },
    ],
    [
        {
            label: 'Log out',
            icon: 'i-lucide-log-out',
            onSelect: () => router.post(route('logout')),
        },
    ],
];
</script>

<template>
    <UApp>
        <FlashToaster />

        <UHeader :to="route('dashboard')">
            <template #title>
                <ApplicationLogo class="h-8 w-auto fill-current text-(--ui-primary)" />
            </template>

            <UNavigationMenu :items="navItems" />

            <template #right>
                <UColorModeButton />

                <UDropdownMenu :items="userMenuItems" :content="{ align: 'end' }">
                    <UButton
                        :label="page.props.auth.user.name"
                        color="neutral"
                        variant="ghost"
                        trailing-icon="i-lucide-chevron-down"
                    />
                </UDropdownMenu>
            </template>
        </UHeader>

        <UMain>
            <UContainer class="py-8">
                <header v-if="$slots.header" class="mb-6">
                    <slot name="header" />
                </header>

                <slot />
            </UContainer>
        </UMain>
    </UApp>
</template>
