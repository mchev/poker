<script setup lang="ts">
import { Form, Head, router, usePoll } from '@inertiajs/vue3';
import {
    CalendarPlus,
    Check,
    LogOut,
    Mail,
    PartyPopper,
    RefreshCw,
    Sparkles,
    Users,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import PokerController from '@/actions/App/Http/Controllers/PokerController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type RoundDate = {
    id: number;
    startsAt: string;
    label: string;
    yesCount: number;
    maybeCount: number;
    yesNames: string[];
    maybeNames: string[];
    noNames: string[];
    reachedThreshold: boolean;
    myVote: 'yes' | 'no' | 'maybe' | null;
    isConfirmed: boolean;
};

type Round = {
    id: number;
    status: 'polling' | 'confirmed' | 'completed';
    minParticipants: number;
    confirmedDate: {
        id: number;
        startsAt: string;
        label: string;
        attendingCount: number;
        attendingNames: string[];
        declinedNames: string[];
    } | null;
    dates: RoundDate[];
};

const props = defineProps<{
    round: Round;
    participant: { name: string } | null;
    subscribedCount: number;
}>();

usePoll(15000, { only: ['round', 'subscribedCount'] });

const selectedVotes = ref<Record<number, string>>(
    Object.fromEntries(
        props.round.dates
            .filter((date) => date.myVote)
            .map((date) => [date.id, date.myVote as string]),
    ),
);

const voteSubmittingForDateId = ref<number | null>(null);

const isPolling = computed(() => props.round.status === 'polling');
const isConfirmed = computed(() => props.round.status === 'confirmed');
const confirmedDate = computed(() => props.round.confirmedDate);

const pokerCard =
    'gap-0 overflow-hidden border border-white/10 bg-black/55 py-0 text-white shadow-[0_18px_54px_rgba(0,0,0,0.75),inset_0_1px_0_rgba(255,255,255,0.06)] backdrop-blur-md';

const pokerHeader =
    'gap-3 border-b border-white/10 bg-black/40 px-6 pt-6 pb-5';

const pokerMuted = 'text-white/60';
const pokerInput =
    'h-12 border-white/10 bg-black/40 text-base text-white placeholder:text-white/35 focus-visible:border-amber-400/45 focus-visible:ring-amber-400/20';
const pokerPanel =
    'rounded-xl border border-white/10 bg-black/35 p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.05)]';
const casinoChipPrimary =
    '!relative !rounded-xl !border !border-amber-400/35 !bg-black/55 !bg-[linear-gradient(180deg,rgba(255,255,255,0.10)_0%,rgba(255,255,255,0.04)_45%,rgba(0,0,0,0.35)_100%)] !px-4 !text-amber-50 shadow-[0_14px_40px_rgba(0,0,0,0.75),0_0_0_1px_rgba(251,191,36,0.10),inset_0_1px_0_rgba(255,255,255,0.10)] backdrop-blur-md hover:!bg-[linear-gradient(180deg,rgba(255,255,255,0.14)_0%,rgba(255,255,255,0.06)_45%,rgba(0,0,0,0.40)_100%)]';

const casinoChipNeutral =
    '!relative !rounded-xl !border !border-white/15 !bg-black/45 !bg-[linear-gradient(180deg,rgba(255,255,255,0.10)_0%,rgba(255,255,255,0.04)_45%,rgba(0,0,0,0.35)_100%)] !px-4 !text-white/90 shadow-[0_14px_40px_rgba(0,0,0,0.75),inset_0_1px_0_rgba(255,255,255,0.10)] backdrop-blur-md hover:!bg-[linear-gradient(180deg,rgba(255,255,255,0.14)_0%,rgba(255,255,255,0.06)_45%,rgba(0,0,0,0.40)_100%)]';

function setVote(dateId: number, vote: string): void {
    selectedVotes.value = { ...selectedVotes.value, [dateId]: vote };

    router.post(
        PokerController.storeVotes.url(),
        { votes: { [dateId]: vote } },
        {
            preserveScroll: true,
            only: ['round', 'subscribedCount'],
            onStart: () => {
                voteSubmittingForDateId.value = dateId;
            },
            onFinish: () => {
                voteSubmittingForDateId.value = null;
            },
        },
    );
}

function formatNames(names: string[]): string {
    return names.length > 0 ? names.join(', ') : '—';
}

const voteOptions = [
    {
        value: 'yes',
        label: 'Partant !',
        icon: Check,
        activeClass:
            '!border-emerald-400/55 !text-emerald-50 shadow-[0_0_0_2px_rgba(52,211,153,0.18),0_14px_40px_rgba(0,0,0,0.75),inset_0_1px_0_rgba(255,255,255,0.10)] !bg-[linear-gradient(180deg,rgba(52,211,153,0.18)_0%,rgba(52,211,153,0.08)_55%,rgba(0,0,0,0.35)_100%)]',
    },
    {
        value: 'maybe',
        label: 'Peut-être',
        icon: CalendarPlus,
        activeClass:
            '!border-amber-400/55 !text-amber-50 shadow-[0_0_0_2px_rgba(251,191,36,0.18),0_14px_40px_rgba(0,0,0,0.75),inset_0_1px_0_rgba(255,255,255,0.10)] !bg-[linear-gradient(180deg,rgba(251,191,36,0.20)_0%,rgba(251,191,36,0.09)_55%,rgba(0,0,0,0.35)_100%)]',
    },
    {
        value: 'no',
        label: 'Pas possible',
        icon: X,
        activeClass:
            '!border-rose-400/55 !text-rose-50 shadow-[0_0_0_2px_rgba(251,113,133,0.18),0_14px_40px_rgba(0,0,0,0.75),inset_0_1px_0_rgba(255,255,255,0.10)] !bg-[linear-gradient(180deg,rgba(251,113,133,0.18)_0%,rgba(251,113,133,0.08)_55%,rgba(0,0,0,0.35)_100%)]',
    },
] as const;
</script>

<template>
    <Head title="Poker party" />

    <div class="space-y-6">
        <section
            class="flex flex-wrap items-center gap-3 rounded-2xl border border-white/10 bg-black/55 px-4 py-3.5 text-sm text-white/80 shadow-lg backdrop-blur-md"
        >
            <Users class="size-4 shrink-0 text-amber-300" />
            <span>
                <strong class="text-amber-200">{{ subscribedCount }}</strong>
                {{
                    subscribedCount > 1
                        ? 'joueurs inscrits'
                        : 'joueur inscrit'
                }}
                · dès que
                <strong class="text-amber-200">{{
                    round.minParticipants
                }}</strong>
                {{
                    round.minParticipants > 1
                        ? ' sont dispo le même soir'
                        : ' est dispo'
                }}, c’est calé !
            </span>
        </section>

        <Card v-if="!participant" :class="pokerCard">
            <CardHeader :class="pokerHeader">
                <div class="flex items-center gap-2 text-amber-300">
                    <Sparkles class="size-5" />
                    <span class="text-sm font-semibold uppercase tracking-wider"
                        >Première fois ?</span
                    >
                </div>
                <CardTitle class="font-serif text-2xl text-white">
                    Rejoins la table
                </CardTitle>
                <CardDescription :class="['text-base', pokerMuted]">
                    Laisse ton mail, on t’envoie un lien perso pour voter et
                    recevoir les infos.
                </CardDescription>
            </CardHeader>
            <CardContent class="px-6 pt-6 pb-6">
                <Form
                    v-bind="PokerController.subscribe.form()"
                    reset-on-success
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="name" class="text-[#dcebe2]"
                            >Ton prénom ou pseudo</Label
                        >
                        <Input
                            id="name"
                            name="name"
                            required
                            autocomplete="name"
                            placeholder="Alex"
                            :class="pokerInput"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email" class="text-[#dcebe2]"
                            >Ton e-mail</Label
                        >
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autocomplete="email"
                            placeholder="alex@exemple.fr"
                            :class="pokerInput"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <Button
                        type="submit"
                        class="h-12 w-full text-base font-semibold"
                        :class="casinoChipPrimary"
                        :disabled="processing"
                    >
                        <Mail class="mr-2 size-4" />
                        {{
                            processing
                                ? 'On envoie…'
                                : 'M’envoyer mon lien'
                        }}
                    </Button>
                </Form>
            </CardContent>
        </Card>

        <template v-else>
            <section
                class="flex flex-col gap-4 rounded-2xl border border-white/10 bg-black/45 px-4 py-4 backdrop-blur-md sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-sm text-white/85">
                    Salut
                    <strong class="text-amber-200">{{ participant.name }}</strong
                    > !
                </p>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <Form
                        v-bind="PokerController.resendAccessLink.form()"
                        v-slot="{ processing }"
                    >
                        <Button
                            type="submit"
                            variant="outline"
                            class="h-11 w-full border-white/10 bg-black/35 text-white/80 hover:bg-white/5 hover:text-white sm:w-auto"
                            :disabled="processing"
                        >
                            <RefreshCw
                                class="mr-2 size-4"
                                :class="{ 'animate-spin': processing }"
                            />
                            {{
                                processing
                                    ? 'Patience…'
                                    : 'Renvoyer mon lien'
                            }}
                        </Button>
                    </Form>

                    <Form
                        v-bind="PokerController.logout.form()"
                        v-slot="{ processing }"
                    >
                        <Button
                            type="submit"
                            variant="ghost"
                            class="h-11 w-full text-[#8faa9a] hover:bg-white/5 hover:text-[#dcebe2] sm:w-auto"
                            :disabled="processing"
                        >
                            <LogOut class="mr-2 size-4" />
                            Me déconnecter
                        </Button>
                    </Form>
                </div>
            </section>

            <Card v-if="isConfirmed && confirmedDate" :class="pokerCard">
                <CardHeader :class="pokerHeader">
                    <Badge
                        class="w-fit border border-amber-400/30 bg-amber-500/10 px-3 py-1 text-sm font-semibold text-amber-100 hover:bg-amber-500/10"
                    >
                        <PartyPopper class="mr-1.5 inline size-4" />
                        C’est calé !
                    </Badge>
                    <CardTitle class="font-serif text-3xl text-white">
                        {{ confirmedDate.label }}
                    </CardTitle>
                    <CardDescription :class="['text-base', pokerMuted]">
                        Le lieu t’a été envoyé par mail. Tu viens ?
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4 px-6 pt-6 pb-6">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div :class="pokerPanel">
                            <p class="text-xs font-semibold uppercase tracking-wider text-amber-300/90">
                                Je viens
                            </p>
                            <p class="mt-1 text-sm text-white/85">
                                {{ formatNames(confirmedDate.attendingNames) }}
                            </p>
                        </div>
                        <div :class="pokerPanel">
                            <p class="text-xs font-semibold uppercase tracking-wider text-stone-400">
                                Pas cette fois
                            </p>
                            <p class="mt-1 text-sm text-white/85">
                                {{ formatNames(confirmedDate.declinedNames) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Form
                            v-bind="PokerController.storeAttendance.form()"
                            v-slot="{ processing }"
                            class="flex-1"
                        >
                            <input type="hidden" name="attending" value="yes" />
                            <Button
                                type="submit"
                                class="h-12 w-full text-base font-semibold"
                                :class="casinoChipPrimary"
                                :disabled="processing"
                            >
                                Je viens !
                            </Button>
                        </Form>

                        <Form
                            v-bind="PokerController.storeAttendance.form()"
                            v-slot="{ processing }"
                            class="flex-1"
                        >
                            <input type="hidden" name="attending" value="no" />
                            <Button
                                type="submit"
                                variant="outline"
                                class="h-12 w-full border-white/10 bg-black/40 text-base text-white/85 hover:bg-white/5 hover:text-white"
                                :disabled="processing"
                            >
                                Pas cette fois
                            </Button>
                        </Form>
                    </div>
                </CardContent>
            </Card>

            <Card v-if="isPolling" :class="pokerCard">
                <CardHeader :class="pokerHeader">
                    <CardTitle class="font-serif text-xl text-white">
                        Proposer un créneau
                    </CardTitle>
                </CardHeader>
                <CardContent class="px-6 pt-6 pb-6">
                    <Form
                        v-bind="PokerController.storeProposedDate.form()"
                        reset-on-success
                        class="grid gap-4 sm:grid-cols-[1fr_1fr_auto]"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="date" class="text-[#dcebe2]"
                                >Quel jour ?</Label
                            >
                            <Input
                                id="date"
                                type="date"
                                name="date"
                                required
                                :class="pokerInput"
                            />
                            <InputError :message="errors.date" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="time" class="text-[#dcebe2]"
                                >À quelle heure ?</Label
                            >
                            <Input
                                id="time"
                                type="time"
                                name="time"
                                required
                                value="20:00"
                                :class="pokerInput"
                            />
                            <InputError :message="errors.time" />
                        </div>

                        <div class="flex items-end">
                            <Button
                                type="submit"
                                class="h-12 w-full font-semibold sm:w-auto"
                                :class="casinoChipPrimary"
                                :disabled="processing"
                            >
                                Ajouter
                            </Button>
                        </div>
                    </Form>
                </CardContent>
            </Card>

            <Card v-if="isPolling" :class="pokerCard">
                <CardHeader :class="pokerHeader">
                    <CardTitle class="font-serif text-xl text-white">
                        T’es dispo quand ?
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4 px-6 pt-6 pb-6">
                    <p
                        v-if="round.dates.length === 0"
                        :class="[
                            pokerPanel,
                            'border-dashed p-8 text-center text-[#a8c4b4]',
                        ]"
                    >
                        Personne n’a encore proposé de date… Lance le jeu ! ♠
                    </p>

                    <div
                        v-for="date in round.dates"
                        :key="date.id"
                        class="rounded-xl border p-4 transition-all"
                        :class="
                            date.reachedThreshold
                                ? 'border-amber-400/35 bg-amber-500/5 shadow-[inset_0_0_0_1px_rgba(251,191,36,0.10)]'
                                : pokerPanel
                        "
                    >
                        <div
                            class="mb-4 flex flex-wrap items-start justify-between gap-3"
                        >
                            <div>
                                <p class="font-semibold text-white">
                                    {{ date.label }}
                                </p>
                            </div>
                            <Badge
                                v-if="date.reachedThreshold"
                                class="border border-amber-400/30 bg-amber-500/10 text-amber-100 hover:bg-amber-500/10"
                            >
                                On peut y aller !
                            </Badge>
                        </div>

                        <div class="mb-4 grid gap-2 text-sm sm:grid-cols-3">
                            <div class="rounded-lg bg-black/35 px-3 py-2">
                                <p class="text-xs uppercase tracking-wide text-amber-300/90">
                                    Partants
                                </p>
                                <p class="mt-0.5 text-white/85">
                                    {{ formatNames(date.yesNames) }}
                                </p>
                            </div>
                            <div class="rounded-lg bg-black/35 px-3 py-2">
                                <p class="text-xs uppercase tracking-wide text-white/65">
                                    Peut-être
                                </p>
                                <p class="mt-0.5 text-white/85">
                                    {{ formatNames(date.maybeNames) }}
                                </p>
                            </div>
                            <div class="rounded-lg bg-black/35 px-3 py-2">
                                <p class="text-xs uppercase tracking-wide text-stone-400">
                                    Pas possible
                                </p>
                                <p class="mt-0.5 text-white/85">
                                    {{ formatNames(date.noNames) }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                            <Button
                                v-for="option in voteOptions"
                                :key="option.value"
                                type="button"
                                variant="outline"
                                :class="[
                                    'h-12 justify-center text-base transition-transform active:translate-y-px active:scale-[0.99]',
                                    casinoChipNeutral,
                                    selectedVotes[date.id] === option.value
                                        ? option.activeClass
                                        : 'hover:bg-white/5',
                                ]"
                                :aria-pressed="selectedVotes[date.id] === option.value"
                                :disabled="voteSubmittingForDateId === date.id"
                                @click="setVote(date.id, option.value)"
                            >
                                <component
                                    :is="option.icon"
                                    class="mr-2 size-4"
                                />
                                {{ option.label }}
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </template>
    </div>
</template>
