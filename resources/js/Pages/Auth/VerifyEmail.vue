<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Email Verification" />

        <p class="mb-4 text-sm text-(--ui-text-muted)">
            Thanks for signing up! Before getting started, could you verify your email address by
            clicking on the link we just emailed to you? If you didn't receive the email, we will
            gladly send you another.
        </p>

        <UAlert
            v-if="verificationLinkSent"
            color="success"
            variant="soft"
            title="A new verification link has been sent to the email address you provided during registration."
            class="mb-4"
        />

        <form class="flex items-center justify-between" @submit.prevent="submit">
            <UButton type="submit" :loading="form.processing">Resend Verification Email</UButton>

            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="text-sm text-(--ui-text-muted) underline hover:text-(--ui-text)"
            >
                Log Out
            </Link>
        </form>
    </GuestLayout>
</template>
