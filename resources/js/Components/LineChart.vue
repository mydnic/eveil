<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    data: { type: Array, required: true }, // [{ date: 'YYYY-MM-DD', value: Number }]
    color: { type: String, default: 'var(--ui-primary)' },
    formatValue: { type: Function, default: (v) => v.toLocaleString() },
});

const width = 600;
const height = 200;
const padding = { top: 16, right: 12, bottom: 8, left: 36 };
const innerWidth = width - padding.left - padding.right;
const innerHeight = height - padding.top - padding.bottom;

const values = computed(() => props.data.map((d) => d.value));
const maxValue = computed(() => Math.max(1, ...values.value));
const minValue = computed(() => Math.min(0, ...values.value));

function xFor(index) {
    return padding.left + (index / Math.max(1, props.data.length - 1)) * innerWidth;
}

function yFor(value) {
    const range = maxValue.value - minValue.value || 1;
    return padding.top + innerHeight - ((value - minValue.value) / range) * innerHeight;
}

const points = computed(() => props.data.map((d, i) => ({ x: xFor(i), y: yFor(d.value), ...d })));

const linePath = computed(() => points.value.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x} ${p.y}`).join(' '));

const gridLines = computed(() => {
    const steps = 3;

    return Array.from({ length: steps + 1 }, (_, i) => {
        const value = minValue.value + ((maxValue.value - minValue.value) * i) / steps;

        return { y: yFor(value), value };
    });
});

const svgEl = ref(null);
const hoverIndex = ref(null);

function onPointerMove(event) {
    if (!svgEl.value || points.value.length === 0) {
        return;
    }

    const rect = svgEl.value.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * width;

    let closest = 0;
    let closestDistance = Infinity;

    points.value.forEach((point, index) => {
        const distance = Math.abs(point.x - x);

        if (distance < closestDistance) {
            closestDistance = distance;
            closest = index;
        }
    });

    hoverIndex.value = closest;
}

function onPointerLeave() {
    hoverIndex.value = null;
}

const hoverPoint = computed(() => (hoverIndex.value !== null ? points.value[hoverIndex.value] : null));

function formatDate(value) {
    return new Date(value).toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}

function formatTick(value) {
    return new Intl.NumberFormat(undefined, { notation: 'compact' }).format(value);
}
</script>

<template>
    <div class="relative w-full">
        <svg
            ref="svgEl"
            :viewBox="`0 0 ${width} ${height}`"
            class="h-[200px] w-full"
            preserveAspectRatio="none"
            @pointermove="onPointerMove"
            @pointerleave="onPointerLeave"
        >
            <template v-for="grid in gridLines" :key="grid.value">
                <line
                    :x1="padding.left"
                    :x2="width - padding.right"
                    :y1="grid.y"
                    :y2="grid.y"
                    stroke="var(--ui-border)"
                    stroke-width="1"
                />
                <text :x="padding.left - 6" :y="grid.y" text-anchor="end" dominant-baseline="middle" class="fill-(--ui-text-dimmed) text-[9px]">
                    {{ formatTick(grid.value) }}
                </text>
            </template>

            <path
                :d="linePath"
                fill="none"
                :stroke="color"
                stroke-width="2"
                stroke-linejoin="round"
                stroke-linecap="round"
            />

            <circle
                v-if="points.length"
                :cx="points[points.length - 1].x"
                :cy="points[points.length - 1].y"
                r="4"
                :fill="color"
                stroke="var(--ui-bg)"
                stroke-width="2"
            />

            <template v-if="hoverPoint">
                <line
                    :x1="hoverPoint.x"
                    :x2="hoverPoint.x"
                    :y1="padding.top"
                    :y2="height - padding.bottom"
                    stroke="var(--ui-border)"
                    stroke-width="1"
                />
                <circle
                    :cx="hoverPoint.x"
                    :cy="hoverPoint.y"
                    r="4"
                    :fill="color"
                    stroke="var(--ui-bg)"
                    stroke-width="2"
                />
            </template>
        </svg>

        <div
            v-if="hoverPoint"
            class="pointer-events-none absolute top-0 -translate-x-1/2 rounded-(--ui-radius) border border-(--ui-border) bg-(--ui-bg) px-2 py-1 text-xs whitespace-nowrap shadow-sm"
            :style="{ left: `${(hoverPoint.x / width) * 100}%` }"
        >
            <div class="font-medium text-(--ui-text-highlighted)">{{ formatValue(hoverPoint.value) }}</div>
            <div class="text-(--ui-text-muted)">{{ formatDate(hoverPoint.date) }}</div>
        </div>
    </div>
</template>
