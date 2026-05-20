<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, History } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { home } from '@/routes';

type PastNight = {
    id: number;
    startsAt: string;
    label: string;
    location: string | null;
    theme: string | null;
    attendingCount: number;
    attendingNames: string[];
    declinedNames: string[];
};

defineProps<{
    pastNights: PastNight[];
    participant: { id: number; name: string } | null;
}>();

const pokerCard =
    'gap-0 overflow-hidden border border-white/10 bg-black/55 py-0 text-white shadow-[0_18px_54px_rgba(0,0,0,0.75),inset_0_1px_0_rgba(255,255,255,0.06)] backdrop-blur-md';

const pokerHeader =
    'gap-3 border-b border-white/10 bg-black/40 px-6 pt-6 pb-5';

const pokerMuted = 'text-white/60';
const pokerPanel =
    'rounded-xl border border-white/10 bg-black/35 p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.05)]';

function formatNames(names: string[]): string {
    return names.length > 0 ? names.join(', ') : '—';
}
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
                        'border-dashed p-8 text-center text-white/60',
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
                    <h2 class="font-serif text-xl font-semibold text-white">
                        {{ night.label }}
                    </h2>
                    <div
                        class="mt-2 flex flex-wrap items-center gap-2 text-sm text-white/60"
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
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div>
                            <p
                                class="text-xs font-semibold uppercase tracking-wider text-amber-300/90"
                            >
                                Étaient là
                            </p>
                            <p class="mt-1 text-sm text-white/85">
                                {{ formatNames(night.attendingNames) }}
                            </p>
                        </div>
                        <div>
                            <p
                                class="text-xs font-semibold uppercase tracking-wider text-stone-400"
                            >
                                Absents
                            </p>
                            <p class="mt-1 text-sm text-white/85">
                                {{ formatNames(night.declinedNames) }}
                            </p>
                        </div>
                    </div>
                </article>
            </CardContent>
        </Card>
    </div>
</template>
