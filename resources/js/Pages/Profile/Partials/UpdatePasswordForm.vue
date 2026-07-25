<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.inputRef?.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.inputRef?.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium">Update Password</h2>

            <p class="mt-1 text-sm text-(--ui-text-muted)">
                Ensure your account is using a long, random password to stay secure.
            </p>
        </header>

        <form class="mt-6 flex flex-col gap-6" @submit.prevent="updatePassword">
            <UFormField label="Current Password" :error="form.errors.current_password">
                <UInput
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="w-full"
                    autocomplete="current-password"
                />
            </UFormField>

            <UFormField label="New Password" :error="form.errors.password">
                <UInput
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="w-full"
                    autocomplete="new-password"
                />
            </UFormField>

            <UFormField label="Confirm Password" :error="form.errors.password_confirmation">
                <UInput
                    v-model="form.password_confirmation"
                    type="password"
                    class="w-full"
                    autocomplete="new-password"
                />
            </UFormField>

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
