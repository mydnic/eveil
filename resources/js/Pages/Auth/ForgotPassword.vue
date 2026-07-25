<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Forgot Password" />

        <p class="mb-4 text-sm text-(--ui-text-muted)">
            Forgot your password? No problem. Just let us know your email address and we will
            email you a password reset link that will allow you to choose a new one.
        </p>

        <UAlert v-if="status" color="success" variant="soft" :title="status" class="mb-4" />

        <form class="flex flex-col gap-6" @submit.prevent="submit">
            <UFormField label="Email" :error="form.errors.email">
                <UInput
                    v-model="form.email"
                    type="email"
                    class="w-full"
                    required
                    autofocus
                    autocomplete="username"
                />
            </UFormField>

            <div class="flex items-center justify-end">
                <UButton type="submit" :loading="form.processing">
                    Email Password Reset Link
                </UButton>
            </div>
        </form>
    </GuestLayout>
</template>
