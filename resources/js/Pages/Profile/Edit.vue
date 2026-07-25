<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold">Profile</h2>
        </template>

        <div class="flex max-w-2xl flex-col gap-6">
            <UCard>
                <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
            </UCard>

            <UCard>
                <UpdatePasswordForm />
            </UCard>

            <UCard>
                <DeleteUserForm />
            </UCard>
        </div>
    </AuthenticatedLayout>
</template>
