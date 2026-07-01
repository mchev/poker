<script setup lang="ts">
import { Badge } from '@/components/ui/badge';

type Game = {
    id: number;
    name: string;
    slug: string;
    icon: string | null;
};

defineProps<{
    games: Game[];
}>();
</script>

<template>
    <template v-for="game in games" :key="game.id">
        <Badge
            class="border border-amber-400/25 bg-amber-500/8 text-amber-100/85 hover:bg-amber-500/8"
        >
            {{ game.icon ?? '' }}
            {{ game.name }}
        </Badge>
    </template>
</template>
