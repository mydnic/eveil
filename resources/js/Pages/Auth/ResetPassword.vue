<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Reset Password" />

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

            <UFormField label="Password" :error="form.errors.password">
                <UInput
                    v-model="form.password"
                    type="password"
                    class="w-full"
                    required
                    autocomplete="new-password"
                />
            </UFormField>

            <UFormField label="Confirm password" :error="form.errors.password_confirmation">
                <UInput
                    v-model="form.password_confirmation"
                    type="password"
                    class="w-full"
                    required
                    autocomplete="new-password"
                />
            </UFormField>

            <div class="flex items-center justify-end">
                <UButton type="submit" :loading="form.processing">Reset Password</UButton>
            </div>
        </form>
    </GuestLayout>
</template>
