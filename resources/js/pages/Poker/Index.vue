<script setup lang="ts">
import { Form, Head, usePoll } from '@inertiajs/vue3';
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

const isPolling = computed(() => props.round.status === 'polling');
const isConfirmed = computed(() => props.round.status === 'confirmed');
const confirmedDate = computed(() => props.round.confirmedDate);

const pokerCard =
    'gap-0 overflow-hidden border-2 border-amber-500/30 bg-[#0d4a32]/95 py-0 text-[#f5f0e1] shadow-[0_16px_48px_rgba(0,0,0,0.55),inset_0_1px_0_rgba(255,255,255,0.08)] backdrop-blur-sm';

const pokerHeader =
    'gap-3 border-b border-amber-500/20 bg-[#0a3d28]/80 px-6 pt-6 pb-5';

const pokerMuted = 'text-[#a8c4b4]';
const pokerInput =
    'h-12 border-amber-500/25 bg-[#082818] text-base text-[#faf6ed] placeholder:text-[#6a8a78] focus-visible:border-amber-400/50 focus-visible:ring-amber-400/20';
const pokerPanel =
    'rounded-xl border border-amber-500/20 bg-[#082818]/70 p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.04)]';
const goldButton =
    'border-amber-500/50 bg-gradient-to-b from-amber-400 to-amber-600 text-stone-950 shadow-[0_4px_16px_rgba(212,175,55,0.25)] hover:from-amber-300 hover:to-amber-500 hover:text-stone-950';

function setVote(dateId: number, vote: string): void {
    selectedVotes.value = { ...selectedVotes.value, [dateId]: vote };
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
            'border-emerald-500 bg-emerald-700 text-white shadow-[0_0_0_2px_rgba(212,175,55,0.5)] hover:bg-emerald-700',
    },
    {
        value: 'maybe',
        label: 'Peut-être',
        icon: CalendarPlus,
        activeClass:
            'border-amber-500 bg-amber-500 text-stone-950 hover:bg-amber-500',
    },
    {
        value: 'no',
        label: 'Pas possible',
        icon: X,
        activeClass:
            'border-stone-500 bg-stone-700 text-white hover:bg-stone-700',
    },
] as const;
</script>

<template>
    <Head title="Poker party" />

    <div class="space-y-6">
        <section
            class="flex flex-wrap items-center gap-3 rounded-2xl border border-amber-500/25 bg-[#0a3d28]/90 px-4 py-3.5 text-sm text-[#dcebe2] shadow-lg"
        >
            <Users class="size-4 shrink-0 text-amber-400" />
            <span>
                <strong class="text-amber-300">{{ subscribedCount }}</strong>
                {{
                    subscribedCount > 1
                        ? 'joueurs inscrits'
                        : 'joueur inscrit'
                }}
                · dès que
                <strong class="text-amber-300">{{
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
                <div class="flex items-center gap-2 text-amber-400">
                    <Sparkles class="size-5" />
                    <span class="text-sm font-semibold uppercase tracking-wider"
                        >Première fois ?</span
                    >
                </div>
                <CardTitle class="font-serif text-2xl text-amber-50">
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
                        :class="goldButton"
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
                class="flex flex-col gap-4 rounded-2xl border border-amber-500/20 bg-[#0a3d28]/70 px-4 py-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-sm text-[#dcebe2]">
                    Salut
                    <strong class="text-amber-300">{{ participant.name }}</strong
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
                            class="h-11 w-full border-amber-500/30 bg-transparent text-amber-100 hover:bg-amber-500/10 hover:text-amber-50 sm:w-auto"
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
                        class="w-fit border border-emerald-500/30 bg-emerald-800/80 px-3 py-1 text-sm font-semibold text-emerald-100 hover:bg-emerald-800/80"
                    >
                        <PartyPopper class="mr-1.5 inline size-4" />
                        C’est calé !
                    </Badge>
                    <CardTitle class="font-serif text-3xl text-amber-50">
                        {{ confirmedDate.label }}
                    </CardTitle>
                    <CardDescription :class="['text-base', pokerMuted]">
                        Le lieu t’a été envoyé par mail. Tu viens ?
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4 px-6 pt-6 pb-6">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div :class="pokerPanel">
                            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-400/90">
                                Je viens
                            </p>
                            <p class="mt-1 text-sm text-[#dcebe2]">
                                {{ formatNames(confirmedDate.attendingNames) }}
                            </p>
                        </div>
                        <div :class="pokerPanel">
                            <p class="text-xs font-semibold uppercase tracking-wider text-stone-400">
                                Pas cette fois
                            </p>
                            <p class="mt-1 text-sm text-[#dcebe2]">
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
                                :class="goldButton"
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
                                class="h-12 w-full border-amber-500/30 bg-[#082818] text-base text-[#dcebe2] hover:bg-[#0a3d28] hover:text-amber-50"
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
                    <CardTitle class="font-serif text-xl text-amber-50">
                        Proposer un créneau
                    </CardTitle>
                    <CardDescription :class="pokerMuted">
                        Une date qui te arrange ? Ajoute-la et on vote.
                    </CardDescription>
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
                                :class="goldButton"
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
                    <CardTitle class="font-serif text-xl text-amber-50">
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
                                ? 'border-emerald-500/40 bg-emerald-950/40 shadow-[inset_0_0_0_1px_rgba(16,185,129,0.2)]'
                                : pokerPanel
                        "
                    >
                        <div
                            class="mb-4 flex flex-wrap items-start justify-between gap-3"
                        >
                            <div>
                                <p class="font-semibold text-amber-50">
                                    {{ date.label }}
                                </p>
                            </div>
                            <Badge
                                v-if="date.reachedThreshold"
                                class="border border-emerald-500/30 bg-emerald-800/80 text-emerald-100 hover:bg-emerald-800/80"
                            >
                                On peut y aller !
                            </Badge>
                        </div>

                        <div class="mb-4 grid gap-2 text-sm sm:grid-cols-3">
                            <div class="rounded-lg bg-[#061f14]/60 px-3 py-2">
                                <p class="text-xs uppercase tracking-wide text-emerald-400/90">
                                    Partants
                                </p>
                                <p class="mt-0.5 text-[#dcebe2]">
                                    {{ formatNames(date.yesNames) }}
                                </p>
                            </div>
                            <div class="rounded-lg bg-[#061f14]/60 px-3 py-2">
                                <p class="text-xs uppercase tracking-wide text-amber-400/90">
                                    Peut-être
                                </p>
                                <p class="mt-0.5 text-[#dcebe2]">
                                    {{ formatNames(date.maybeNames) }}
                                </p>
                            </div>
                            <div class="rounded-lg bg-[#061f14]/60 px-3 py-2">
                                <p class="text-xs uppercase tracking-wide text-stone-400">
                                    Pas possible
                                </p>
                                <p class="mt-0.5 text-[#dcebe2]">
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
                                class="h-12 justify-center border-amber-500/25 bg-[#082818] text-base text-[#dcebe2]"
                                :class="
                                    selectedVotes[date.id] === option.value
                                        ? option.activeClass
                                        : 'hover:bg-[#0a3d28]'
                                "
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

                    <Form
                        v-if="round.dates.length > 0"
                        v-bind="PokerController.storeVotes.form()"
                        v-slot="{ processing }"
                        class="space-y-4"
                    >
                        <template
                            v-for="(vote, dateId) in selectedVotes"
                            :key="dateId"
                        >
                            <input
                                type="hidden"
                                :name="`votes[${dateId}]`"
                                :value="vote"
                            />
                        </template>

                        <Button
                            type="submit"
                            class="h-12 w-full text-base font-semibold"
                            :class="goldButton"
                            :disabled="
                                processing ||
                                Object.keys(selectedVotes).length === 0
                            "
                        >
                            {{
                                processing
                                    ? 'On enregistre…'
                                    : 'Valider mes réponses'
                            }}
                        </Button>
                    </Form>
                </CardContent>
            </Card>
        </template>
    </div>
</template>
