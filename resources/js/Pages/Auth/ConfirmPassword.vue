<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Confirm Password" />

        <p class="mb-4 text-sm text-(--ui-text-muted)">
            This is a secure area of the application. Please confirm your password before
            continuing.
        </p>

        <form class="flex flex-col gap-6" @submit.prevent="submit">
            <UFormField label="Password" :error="form.errors.password">
                <UInput
                    v-model="form.password"
                    type="password"
                    class="w-full"
                    required
                    autocomplete="current-password"
                    autofocus
                />
            </UFormField>

            <div class="flex justify-end">
                <UButton type="submit" :loading="form.processing">Confirm</UButton>
            </div>
        </form>
    </GuestLayout>
</template>
