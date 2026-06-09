<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, History, Trophy } from 'lucide-vue-next';
import { ref } from 'vue';
import PokerController from '@/actions/App/Http/Controllers/PokerController';
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

type Attendee = {
    id: number;
    name: string;
};

type PastNight = {
    id: number;
    startsAt: string;
    label: string;
    location: string | null;
    theme: string | null;
    beginnersWelcome: boolean;
    note: string | null;
    attendingCount: number;
    attendees: Attendee[];
    winnerParticipantId: number | null;
    winnerName: string | null;
};

const props = defineProps<{
    pastNights: PastNight[];
    participant: { id: number; name: string } | null;
}>();

const updatingNightId = ref<number | null>(null);

function toggleWinner(night: PastNight, attendeeId: number): void {
    if (!props.participant) {
        return;
    }

    const winnerParticipantId =
        night.winnerParticipantId === attendeeId ? null : attendeeId;

    updatingNightId.value = night.id;

    router.patch(
        PokerController.updatePastNightWinner.url(night.id),
        { winner_participant_id: winnerParticipantId },
        {
            preserveScroll: true,
            onFinish: () => {
                updatingNightId.value = null;
            },
        },
    );
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
                        <Badge
                            v-if="night.winnerName"
                            class="border border-amber-400/35 bg-amber-500/15 text-amber-100 hover:bg-amber-500/15"
                        >
                            <Trophy class="mr-1 inline size-3.5" />
                            {{ night.winnerName }} a gagné
                        </Badge>
                    </div>
                    <p
                        v-if="night.note"
                        class="mt-2 text-sm text-white/75"
                    >
                        {{ night.note }}
                    </p>

                    <div class="mt-4 space-y-2">
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-amber-300/90"
                        >
                            {{
                                participant
                                    ? 'Étaient là — coche le gagnant'
                                    : 'Étaient là'
                            }}
                        </p>

                        <p
                            v-if="night.attendees.length === 0"
                            class="text-sm text-white/60"
                        >
                            —
                        </p>

                        <ul v-else class="space-y-2">
                            <li
                                v-for="attendee in night.attendees"
                                :key="attendee.id"
                            >
                                <label
                                    v-if="participant"
                                    class="flex cursor-pointer items-center gap-3 rounded-lg border border-white/10 bg-black/35 px-3 py-2.5 transition-colors hover:bg-black/50"
                                    :class="{
                                        'border-amber-400/35 bg-amber-500/10':
                                            night.winnerParticipantId ===
                                            attendee.id,
                                    }"
                                >
                                    <input
                                        type="checkbox"
                                        class="size-5 shrink-0 rounded border-white/20 bg-black/50 text-amber-500 focus:ring-amber-400/40"
                                        :checked="
                                            night.winnerParticipantId ===
                                            attendee.id
                                        "
                                        :disabled="
                                            updatingNightId === night.id
                                        "
                                        @change="toggleWinner(night, attendee.id)"
                                    />
                                    <span
                                        class="flex flex-1 items-center gap-2 text-sm text-white/90"
                                    >
                                        {{ attendee.name }}
                                        <Trophy
                                            v-if="
                                                night.winnerParticipantId ===
                                                attendee.id
                                            "
                                            class="size-4 text-amber-300"
                                        />
                                    </span>
                                </label>

                                <p
                                    v-else
                                    class="rounded-lg border border-white/10 bg-black/35 px-3 py-2 text-sm text-white/85"
                                    :class="{
                                        'border-amber-400/35 bg-amber-500/10 font-semibold text-amber-100':
                                            night.winnerParticipantId ===
                                            attendee.id,
                                    }"
                                >
                                    <Trophy
                                        v-if="
                                            night.winnerParticipantId ===
                                            attendee.id
                                        "
                                        class="mr-1.5 inline size-4 text-amber-300"
                                    />
                                    {{ attendee.name }}
                                </p>
                            </li>
                        </ul>
                    </div>
                </article>
            </CardContent>
        </Card>
    </div>
</template>
