<script setup lang="ts">
defineProps<{
    color?: 'red' | 'blue' | 'gold' | 'black';
    size?: number;
}>();
</script>

<template>
    <svg
        :width="size ?? 56"
        :height="size ?? 56"
        viewBox="0 0 56 56"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
    >
        <circle
            cx="28"
            cy="28"
            r="26"
            :fill="
                color === 'blue'
                    ? '#1e4d8c'
                    : color === 'gold'
                      ? '#c8921a'
                      : color === 'black'
                        ? '#1f1f1f'
                        : '#8b1e1e'
            "
            stroke="#f5e6c8"
            stroke-width="2"
        />
        <circle
            cx="28"
            cy="28"
            r="20"
            fill="none"
            stroke="#f5e6c8"
            stroke-width="1.5"
            stroke-dasharray="4 3"
            opacity="0.85"
        />
        <circle cx="28" cy="28" r="14" fill="none" stroke="#faf6ed" stroke-width="1" opacity="0.35" />
        <text
            x="28"
            y="32"
            text-anchor="middle"
            fill="#faf6ed"
            font-size="11"
            font-weight="700"
            font-family="Georgia, serif"
        >
            ♠
        </text>
    </svg>
</template>
