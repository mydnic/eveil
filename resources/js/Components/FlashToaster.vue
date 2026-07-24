<script setup>
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';

const page = usePage();
const toast = useToast();

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            toast.add({ title: flash.success, color: 'success' });
        }

        if (flash?.error) {
            toast.add({ title: flash.error, color: 'error' });
        }
    },
    { deep: true },
);
</script>

<template></template>
