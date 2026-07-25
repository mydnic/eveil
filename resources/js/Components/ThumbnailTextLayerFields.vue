<script setup>
defineProps({
    layer: { type: Object, required: true },
    fonts: { type: Array, required: true },
    label: { type: String, default: null },
    removable: { type: Boolean, default: false },
});

defineEmits(['remove']);
</script>

<template>
    <UCard>
        <template #header>
            <div class="flex items-center justify-between gap-2">
                <h3 v-if="label" class="font-medium">{{ label }}</h3>
                <UInput
                    v-else
                    v-model="layer.content"
                    placeholder="Fixed text, e.g. average dad beats video game bosses"
                    class="w-full"
                />
                <UButton
                    v-if="removable"
                    icon="i-lucide-trash-2"
                    color="error"
                    variant="ghost"
                    size="sm"
                    class="shrink-0"
                    @click="$emit('remove')"
                />
            </div>
        </template>

        <div class="flex flex-col gap-4">
            <div class="flex flex-wrap gap-4">
                <UFormField label="Font">
                    <USelect v-model="layer.font" :items="fonts" class="w-44" />
                </UFormField>

                <UFormField label="Color">
                    <input
                        v-model="layer.font_color"
                        type="color"
                        class="h-9 w-16 cursor-pointer rounded-(--ui-radius) border border-(--ui-border) bg-transparent p-1"
                    />
                </UFormField>

                <UFormField label="Size">
                    <UInput v-model.number="layer.font_size" type="number" min="10" max="200" class="w-20" />
                </UFormField>

                <UFormField label="Uppercase">
                    <UCheckbox v-model="layer.uppercase" />
                </UFormField>
            </div>

            <div class="flex flex-wrap gap-4">
                <UFormField label="X position (%)" description="0 = left edge, 100 = right edge">
                    <UInput v-model.number="layer.x_percent" type="number" min="0" max="100" class="w-20" />
                </UFormField>

                <UFormField label="Y position (%)" description="0 = top, 100 = bottom">
                    <UInput v-model.number="layer.y_percent" type="number" min="0" max="100" class="w-20" />
                </UFormField>

                <UFormField label="Align">
                    <USelect
                        v-model="layer.align"
                        :items="[
                            { label: 'Left', value: 'left' },
                            { label: 'Center', value: 'center' },
                            { label: 'Right', value: 'right' },
                        ]"
                        class="w-32"
                    />
                </UFormField>

                <UFormField label="Rotation (°)">
                    <UInput v-model.number="layer.rotation" type="number" min="-180" max="180" class="w-20" />
                </UFormField>
            </div>

            <div class="flex flex-wrap gap-4">
                <UFormField label="Stroke color">
                    <input
                        v-model="layer.stroke_color"
                        type="color"
                        class="h-9 w-16 cursor-pointer rounded-(--ui-radius) border border-(--ui-border) bg-transparent p-1"
                    />
                </UFormField>

                <UFormField label="Stroke width">
                    <UInput v-model.number="layer.stroke_width" type="number" min="0" max="20" class="w-20" />
                </UFormField>
            </div>
        </div>
    </UCard>
</template>
