<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, History } from 'lucide-vue-next';
import PokerNameList from '@/components/poker/PokerNameList.vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    pokerCard,
    pokerHeader,
    pokerMuted,
    pokerPanel,
} from '@/lib/pokerUi';
import { home } from '@/routes';

type PastNight = {
    id: number;
    startsAt: string;
    label: string;
    location: string | null;
    theme: string | null;
    beginnersWelcome: boolean;
    note: string | null;
    attendingCount: number;
    attendingNames: string[];
    declinedNames: string[];
};

defineProps<{
    pastNights: PastNight[];
    participant: { id: number; name: string } | null;
}>();
</script>

<template>
    <Head title="Soirées passées" />

    <div class="space-y-6">
        <Link
            :href="home.url()"
            class="inline-flex items-center gap-2 text-sm font-medium text-amber-300/90 transition-colors hover:text-amber-200"
        >
            <ArrowLeft class="size-4" />
            Retour au sondage
        </Link>

        <Card :class="pokerCard">
            <CardHeader :class="pokerHeader">
                <div class="flex items-center gap-2 text-amber-300">
                    <History class="size-5" />
                    <span class="text-sm font-semibold uppercase tracking-wider"
                        >Archives</span
                    >
                </div>
                <CardTitle class="font-serif text-2xl text-white">
                    Soirées passées
                </CardTitle>
                <CardDescription :class="['text-base', pokerMuted]">
                    Les tables où on s’est déjà fait plumer.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4 px-6 pt-6 pb-6">
                <p
                    v-if="pastNights.length === 0"
                    :class="[
                        pokerPanel,
                        'border-dashed p-8 text-center text-white/70',
                    ]"
                >
                    Aucune soirée passée pour l’instant. La première arrivera
                    bientôt ! ♠
                </p>

                <article
                    v-for="night in pastNights"
                    :key="night.id"
                    :class="pokerPanel"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <h2 class="font-serif text-xl font-semibold text-white">
                            {{ night.label }}
                        </h2>
                        <p
                            class="font-serif text-3xl font-bold tabular-nums text-amber-300"
                            :aria-label="`${night.attendingCount} présents`"
                        >
                            {{ night.attendingCount }}
                            <span class="text-sm font-normal text-white/60"
                                >présents</span
                            >
                        </p>
                    </div>
                    <div
                        class="mt-2 flex flex-wrap items-center gap-2 text-sm text-white/70"
                    >
                        <span v-if="night.location">
                            {{ night.location }}
                        </span>
                        <Badge
                            v-if="night.theme"
                            class="border border-white/10 bg-white/5 text-white/75 hover:bg-white/5"
                        >
                            {{ night.theme }}
                        </Badge>
                        <Badge
                            v-if="night.beginnersWelcome"
                            class="border border-sky-400/30 bg-sky-500/10 text-sky-100 hover:bg-sky-500/10"
                        >
                            Débutant·e·s OK
                        </Badge>
                    </div>
                    <p
                        v-if="night.note"
                        class="mt-2 text-sm text-white/75"
                    >
                        {{ night.note }}
                    </p>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <PokerNameList
                            :names="night.attendingNames"
                            label="Étaient là"
                            label-class="text-amber-300/90"
                        />
                        <PokerNameList
                            :names="night.declinedNames"
                            label="Absents"
                            label-class="text-stone-400"
                        />
                    </div>
                </article>
            </CardContent>
        </Card>
    </div>
</template>
