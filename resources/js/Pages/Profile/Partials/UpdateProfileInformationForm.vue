<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium">Profile Information</h2>

            <p class="mt-1 text-sm text-(--ui-text-muted)">
                Update your account's profile information and email address.
            </p>
        </header>

        <form class="mt-6 flex flex-col gap-6" @submit.prevent="form.patch(route('profile.update'))">
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

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="text-sm text-(--ui-text-muted)">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="text-(--ui-text-muted) underline hover:text-(--ui-text)"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <UAlert
                    v-if="status === 'verification-link-sent'"
                    color="success"
                    variant="soft"
                    title="A new verification link has been sent to your email address."
                    class="mt-2"
                />
            </div>

            <div class="flex items-center gap-4">
                <UButton type="submit" :loading="form.processing">Save</UButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-(--ui-text-muted)">
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
