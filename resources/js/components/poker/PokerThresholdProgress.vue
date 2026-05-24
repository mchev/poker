<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    yesCount: number;
    threshold: number;
}>();

const percent = computed(() =>
    Math.min(100, Math.round((props.yesCount / props.threshold) * 100)),
);
</script>

<template>
    <div class="space-y-1.5">
        <div
            class="flex items-center justify-between text-xs text-white/70"
            aria-hidden="true"
        >
            <span>{{ yesCount }} / {{ threshold }} partants</span>
            <span>{{ percent }}%</span>
        </div>
        <div
            class="h-2 overflow-hidden rounded-full bg-black/50"
            role="progressbar"
            :aria-valuenow="yesCount"
            :aria-valuemin="0"
            :aria-valuemax="threshold"
            :aria-label="`${yesCount} partants sur ${threshold}`"
        >
            <div
                class="h-full rounded-full bg-gradient-to-r from-amber-600/80 to-amber-400/90 transition-all duration-500"
                :style="{ width: `${percent}%` }"
            />
        </div>
    </div>
</template>
