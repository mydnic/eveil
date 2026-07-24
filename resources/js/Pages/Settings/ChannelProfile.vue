<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    profile: Object,
});

const form = useForm({
    name: props.profile.name ?? '',
    tagline: props.profile.tagline ?? '',
    tone_of_voice: props.profile.tone_of_voice ?? '',
    audience: props.profile.audience ?? '',
    extra_instructions: props.profile.extra_instructions ?? '',
});

function save() {
    form.put(route('settings.channel-profile.update'), { preserveScroll: true });
}
</script>

<template>
    <Head title="Channel profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold">Channel profile</h2>
        </template>

        <p class="mb-6 max-w-2xl text-(--ui-text-muted)">
            Tell the AI a bit about your channel. This context is used whenever it writes or
            suggests content for you, such as video descriptions.
        </p>

        <form class="flex max-w-2xl flex-col gap-6" @submit.prevent="save">
            <UFormField label="Channel name" :error="form.errors.name">
                <UInput v-model="form.name" class="w-full" placeholder="e.g. Boss Rush Archive" />
            </UFormField>

            <UFormField label="Tagline" description="A short line describing what the channel is about." :error="form.errors.tagline">
                <UTextarea v-model="form.tagline" :rows="2" autoresize class="w-full" />
            </UFormField>

            <UFormField
                label="Tone of voice"
                description="How should the AI sound? e.g. casual and a bit sarcastic, calm and informative, hype and energetic..."
                :error="form.errors.tone_of_voice"
            >
                <UTextarea v-model="form.tone_of_voice" :rows="2" autoresize class="w-full" />
            </UFormField>

            <UFormField
                label="Audience"
                description="Who watches this channel? e.g. Souls-like fans, intermediate to hardcore gamers..."
                :error="form.errors.audience"
            >
                <UTextarea v-model="form.audience" :rows="2" autoresize class="w-full" />
            </UFormField>

            <UFormField
                label="Additional instructions"
                description="Anything else the AI should always know or do — a standard sign-off, links to include, things to avoid, etc."
                :error="form.errors.extra_instructions"
            >
                <UTextarea v-model="form.extra_instructions" :rows="4" autoresize class="w-full" />
            </UFormField>

            <UButton type="submit" :loading="form.processing" class="self-start">Save profile</UButton>
        </form>
    </AuthenticatedLayout>
</template>
