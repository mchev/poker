<script setup lang="ts">
import PokerChip from '@/components/poker/PokerChip.vue';
</script>

<template>
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <PokerChip
            color="red"
            :size="52"
            class="poker-float absolute -left-2 top-24 opacity-70"
            style="animation-delay: 0s"
        />
        <PokerChip
            color="gold"
            :size="44"
            class="poker-float absolute right-4 top-40 opacity-60"
            style="animation-delay: 1.5s"
        />
        <PokerChip
            color="blue"
            :size="40"
            class="poker-float absolute bottom-32 left-6 opacity-50"
            style="animation-delay: 3s"
        />
        <PokerChip
            color="black"
            :size="36"
            class="poker-float absolute bottom-48 right-0 opacity-45"
            style="animation-delay: 4.5s"
        />
    </div>
</template>

<style scoped>
.poker-float {
    animation: chip-float 7s ease-in-out infinite;
    filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.45));
}

@keyframes chip-float {
    0%,
    100% {
        transform: translateY(0) rotate(-8deg);
    }
    50% {
        transform: translateY(-14px) rotate(8deg);
    }
}
</style>
