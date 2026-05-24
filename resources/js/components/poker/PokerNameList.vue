<script setup lang="ts">
import { ref } from 'vue';
import { formatNamesList } from '@/lib/pokerUi';

const props = withDefaults(
    defineProps<{
        names: string[];
        label: string;
        labelClass?: string;
        maxVisible?: number;
    }>(),
    {
        maxVisible: 4,
    },
);

const expanded = ref(false);

const formatted = () => formatNamesList(props.names, props.maxVisible);
</script>

<template>
    <div class="rounded-lg bg-black/35 px-3 py-2">
        <p class="text-xs uppercase tracking-wide" :class="labelClass">
            {{ label }}
        </p>
        <p class="mt-0.5 text-sm text-white/85">
            <template v-if="expanded || formatted().extra === 0">
                {{ names.length > 0 ? names.join(', ') : '—' }}
            </template>
            <template v-else>
                {{ formatted().visible }}
                <button
                    type="button"
                    class="ml-1 font-medium text-amber-300/90 underline-offset-2 hover:text-amber-200 hover:underline"
                    @click="expanded = true"
                >
                    +{{ formatted().extra }} autre{{
                        formatted().extra > 1 ? 's' : ''
                    }}
                </button>
            </template>
        </p>
    </div>
</template>
