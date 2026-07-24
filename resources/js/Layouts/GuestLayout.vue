<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <UApp>
        <div
            class="flex min-h-screen flex-col items-center justify-center gap-6 bg-(--ui-bg-elevated) px-4"
        >
            <Link href="/">
                <ApplicationLogo class="h-16 w-16 fill-current text-(--ui-primary)" />
            </Link>

            <UCard class="w-full sm:max-w-md">
                <slot />
            </UCard>
        </div>
    </UApp>
</template>
