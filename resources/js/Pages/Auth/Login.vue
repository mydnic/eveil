<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <UAlert v-if="status" color="success" variant="soft" :title="status" class="mb-6" />

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
                    autocomplete="current-password"
                />
            </UFormField>

            <UCheckbox v-model="form.remember" label="Remember me" />

            <div class="flex items-center justify-end gap-4">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-(--ui-text-muted) underline hover:text-(--ui-text)"
                >
                    Forgot your password?
                </Link>

                <UButton type="submit" :loading="form.processing">Log in</UButton>
            </div>
        </form>
    </GuestLayout>
</template>
