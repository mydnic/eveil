<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <form class="flex flex-col gap-6" @submit.prevent="submit">
            <UFormField label="Name" :error="form.errors.name">
                <UInput
                    v-model="form.name"
                    type="text"
                    class="w-full"
                    required
                    autofocus
                    autocomplete="name"
                />
            </UFormField>

            <UFormField label="Email" :error="form.errors.email">
                <UInput
                    v-model="form.email"
                    type="email"
                    class="w-full"
                    required
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

            <div class="flex items-center justify-end gap-4">
                <Link
                    :href="route('login')"
                    class="text-sm text-(--ui-text-muted) underline hover:text-(--ui-text)"
                >
                    Already registered?
                </Link>

                <UButton type="submit" :loading="form.processing">Register</UButton>
            </div>
        </form>
    </GuestLayout>
</template>
