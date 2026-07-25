<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.inputRef?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="flex flex-col gap-6">
        <header>
            <h2 class="text-lg font-medium">Delete Account</h2>

            <p class="mt-1 text-sm text-(--ui-text-muted)">
                Once your account is deleted, all of its resources and data will be permanently
                deleted. Before deleting your account, please download any data or information
                that you wish to retain.
            </p>
        </header>

        <UButton color="error" class="self-start" @click="confirmingUserDeletion = true">
            Delete Account
        </UButton>

        <UModal
            v-model:open="confirmingUserDeletion"
            title="Are you sure you want to delete your account?"
            description="Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account."
            @update:open="(value) => !value && closeModal()"
        >
            <template #body>
                <UFormField label="Password" :error="form.errors.password">
                    <UInput
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="w-full"
                        placeholder="Password"
                        @keyup.enter="deleteUser"
                    />
                </UFormField>
            </template>

            <template #footer>
                <div class="flex w-full justify-end gap-3">
                    <UButton color="neutral" variant="soft" @click="closeModal">Cancel</UButton>

                    <UButton color="error" :loading="form.processing" @click="deleteUser">
                        Delete Account
                    </UButton>
                </div>
            </template>
        </UModal>
    </section>
</template>
