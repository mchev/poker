<script setup lang="ts">
import { Check } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    yesCount: number;
    threshold: number;
}>();

const isReached = computed(() => props.yesCount >= props.threshold);
const extra = computed(() => Math.max(0, props.yesCount - props.threshold));

const barMax = computed(() => Math.max(props.threshold * 2, props.threshold + 5));
const percent = computed(() =>
    Math.min(100, Math.round((props.yesCount / barMax.value) * 100)),
);

const barColor = computed(() =>
    isReached.value
        ? 'bg-gradient-to-r from-emerald-600/80 to-emerald-400/90'
        : 'bg-gradient-to-r from-amber-600/80 to-amber-400/90',
);
</script>

<template>
    <div class="space-y-1.5">
        <div
            class="flex items-center justify-between text-xs"
            aria-hidden="true"
        >
            <span class="text-white/70">
                <template v-if="!isReached">
                    {{ yesCount }} / {{ threshold }} minimum
                </template>
                <template v-else>
                    <span class="font-medium text-emerald-300">
                        <Check class="mr-0.5 inline size-3.5 align-text-top" />
                        Minimum atteint
                    </span>
                    <span v-if="extra > 0" class="ml-1 text-white/55">
                        · {{ yesCount }} participants
                    </span>
                </template>
            </span>
            <span class="text-white/50">
                <template v-if="!isReached">Pas de limite</template>
                <template v-else>Plus on est de fous…</template>
            </span>
        </div>

        <div
            class="relative h-2 overflow-hidden rounded-full bg-black/50"
            role="progressbar"
            :aria-valuenow="yesCount"
            :aria-valuemin="0"
            :aria-valuemax="threshold"
            :aria-label="`${yesCount} participants, minimum ${threshold} requis`"
        >
            <div
                class="h-full rounded-full transition-all duration-500"
                :class="barColor"
                :style="{ width: `${percent}%` }"
            />
            <div
                class="absolute right-[calc(50%-1px)] top-0 h-full w-0.5 rounded-full transition-opacity duration-500"
                :class="isReached ? 'bg-emerald-300/60' : 'bg-white/30'"
            />
        </div>

        <p v-if="isReached" class="text-[11px] leading-tight text-emerald-300/65">
            Le minimum est atteint&nbsp;! Pas de maximum — tout le monde peut encore se greffer.
        </p>
    </div>
</template>
