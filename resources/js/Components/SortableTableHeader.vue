<script setup>
defineProps({
    column: Object,
    label: String,
});
</script>

<template>
    <UButton
        color="neutral"
        variant="ghost"
        :label="label"
        :icon="
            column.getIsSorted() === 'asc'
                ? 'i-lucide-arrow-up-narrow-wide'
                : column.getIsSorted() === 'desc'
                  ? 'i-lucide-arrow-down-wide-narrow'
                  : 'i-lucide-arrow-up-down'
        "
        class="-mx-2.5"
        @click="column.toggleSorting(column.getIsSorted() === 'asc')"
    />
</template>
