<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import {
    Copy,
    LogIn,
    LogOut,
    Mail,
    Pencil,
    Plus,
    RefreshCw,
    Sparkles,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import PokerController from '@/actions/App/Http/Controllers/PokerController';
import InputError from '@/components/InputError.vue';
import PokerAdminPanel from '@/components/poker/PokerAdminPanel.vue';
import PokerConfirmedCard from '@/components/poker/PokerConfirmedCard.vue';
import PokerLocationFields from '@/components/poker/PokerLocationFields.vue';
import PokerPollDateCard from '@/components/poker/PokerPollDateCard.vue';
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
    casinoChipPrimary,
    minDateForInput,
    pokerCard,
    pokerHeader,
    pokerInput,
    pokerMuted,
    pokerPanel,
} from '@/lib/pokerUi';

type RoundDate = {
    id: number;
    startsAt: string;
    label: string;
    location: string | null;
    theme: string | null;
    beginnersWelcome: boolean;
    note: string | null;
    yesCount: number;
    maybeCount: number;
    yesNames: string[];
    maybeNames: string[];
    noNames: string[];
    reachedThreshold: boolean;
    myVote: 'yes' | 'no' | 'maybe' | null;
    isConfirmed: boolean;
    canDelete: boolean;
    canEditLocation: boolean;
    canRemindNonVoters: boolean;
    nonVoterCount: number;
};

type ConfirmedDate = {
    id: number;
    startsAt: string;
    label: string;
    location: string | null;
    theme: string | null;
    beginnersWelcome: boolean;
    note: string | null;
    myVote: 'yes' | 'no' | 'maybe' | null;
    attendingCount: number;
    attendingNames: string[];
    declinedNames: string[];
    canEditLocation: boolean;
    canEditNote: boolean;
    calendarIcsUrl: string;
    googleCalendarUrl: string;
};

type Round = {
    id: number;
    status: 'polling' | 'confirmed' | 'completed';
    minParticipants: number;
    confirmedDates: ConfirmedDate[];
    dates: RoundDate[];
};

const props = defineProps<{
    round: Round;
    participant: { id: number; name: string; isAdmin: boolean } | null;
    participants: { id: number; name: string }[];
    adminParticipants: { id: number; name: string; email: string }[];
    subscribedCount: number;
    personalUrl: string | null;
}>();

const selectedVotes = ref<Record<number, string>>(
    Object.fromEntries(
        props.round.dates
            .filter((date) => date.myVote)
            .map((date) => [date.id, date.myVote as string]),
    ),
);

const voteSubmittingForDateId = ref<number | null>(null);
const dateDeletingId = ref<number | null>(null);
const proposedLocationType = ref('mine');
const isProposeFormOpen = ref(false);
const editingLocationForDateId = ref<number | null>(null);
const editLocationTypes = ref<Record<number, string>>({});
const isEditingProfile = ref(false);

const isPolling = computed(() => props.round.status === 'polling');
const hasConfirmedDates = computed(
    () => props.round.confirmedDates.length > 0,
);
const showPollNav = computed(
    () => isPolling.value && props.round.dates.length > 0,
);
const showProposeNav = computed(() => isPolling.value && !!props.participant);

const registeredPlayersLabel = computed(
    () =>
        `${props.subscribedCount} ${
            props.subscribedCount > 1 ? 'joueurs inscrits' : 'joueur inscrit'
        }`,
);

const bestOptimisticYesCount = computed(() =>
    props.round.dates.reduce((bestCount, date) => {
        const selectedVote = selectedVotes.value[date.id] ?? date.myVote;
        const yesCount =
            date.yesCount -
            (date.myVote === 'yes' ? 1 : 0) +
            (selectedVote === 'yes' ? 1 : 0);

        return Math.max(bestCount, yesCount);
    }, 0),
);

const pollDescription = computed(() => {
    const threshold = props.round.minParticipants;

    return `${registeredPlayersLabel.value} · ${bestOptimisticYesCount.value}/${threshold} partants sur le meilleur créneau.`;
});

const pollThresholdDescription = computed(() => {
    const threshold = props.round.minParticipants;
    const partantsLabel = threshold > 1 ? 'partants' : 'partant';

    return `Il faut ${threshold} ${partantsLabel} pour valider un créneau.`;
});

const pollOpenParticipationDescription = computed(
    () =>
        'Ensuite, d’autres joueurs peuvent se greffer à la soirée : pas de limite de places.',
);

const minProposeDate = minDateForInput();

function editLocationTypeFor(dateId: number): string {
    return editLocationTypes.value[dateId] ?? 'mine';
}

function setEditLocationType(dateId: number, value: string): void {
    editLocationTypes.value = {
        ...editLocationTypes.value,
        [dateId]: value,
    };
}

function toggleLocationEdit(dateId: number): void {
    if (editingLocationForDateId.value === dateId) {
        editingLocationForDateId.value = null;

        return;
    }

    editingLocationForDateId.value = dateId;
    setEditLocationType(dateId, 'mine');
}

function scrollToSection(id: string): void {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function setVote(dateId: number, vote: string): void {
    selectedVotes.value = { ...selectedVotes.value, [dateId]: vote };

    router.post(
        PokerController.storeVotes.url(),
        { votes: { [dateId]: vote } },
        {
            preserveScroll: true,
            only: ['round', 'subscribedCount', 'adminParticipants'],
            onStart: () => {
                voteSubmittingForDateId.value = dateId;
            },
            onSuccess: () => {
                toast.success('Vote enregistré');
            },
            onFinish: () => {
                voteSubmittingForDateId.value = null;
            },
        },
    );
}

function deleteProposedDate(dateId: number): void {
    if (
        !window.confirm(
            'Supprimer ce créneau ? Les votes associés seront perdus.',
        )
    ) {
        return;
    }

    router.delete(PokerController.destroyProposedDate.url(dateId), {
        preserveScroll: true,
        only: ['round', 'subscribedCount', 'adminParticipants'],
        onStart: () => {
            dateDeletingId.value = dateId;
        },
        onSuccess: () => {
            toast.success('Créneau supprimé');
        },
        onFinish: () => {
            dateDeletingId.value = null;
        },
    });
}

async function copyPersonalLink(): Promise<void> {
    if (!props.personalUrl) {
        return;
    }

    try {
        await navigator.clipboard.writeText(props.personalUrl);
        toast.success('Lien copié dans le presse-papiers');
    } catch {
        toast.error('Impossible de copier le lien');
    }
}
</script>

<template>
    <Head title="Poker party" />

    <div class="space-y-6">
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
                    Trois étapes pour participer aux prochaines soirées.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6 px-6 pt-2 pb-6">
                <ol class="grid gap-3 sm:grid-cols-3">
                    <li :class="[pokerPanel, 'text-sm text-white/85']">
                        <span
                            class="mb-2 inline-flex size-7 items-center justify-center rounded-full bg-amber-500/20 text-sm font-bold text-amber-200"
                            >1</span
                        >
                        <p class="font-medium text-white">Inscris-toi</p>
                        <p class="mt-1 text-white/65">Prénom et e-mail</p>
                    </li>
                    <li :class="[pokerPanel, 'text-sm text-white/85']">
                        <span
                            class="mb-2 inline-flex size-7 items-center justify-center rounded-full bg-amber-500/20 text-sm font-bold text-amber-200"
                            >2</span
                        >
                        <p class="font-medium text-white">Ouvre ton lien</p>
                        <p class="mt-1 text-white/65">Reçu par e-mail</p>
                    </li>
                    <li :class="[pokerPanel, 'text-sm text-white/85']">
                        <span
                            class="mb-2 inline-flex size-7 items-center justify-center rounded-full bg-amber-500/20 text-sm font-bold text-amber-200"
                            >3</span
                        >
                        <p class="font-medium text-white">Vote</p>
                        <p class="mt-1 text-white/65">Dispo et propositions</p>
                    </li>
                </ol>

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
                                : 'Me connecter et recevoir mon lien'
                        }}
                    </Button>
                </Form>

                <div
                    :class="[
                        pokerPanel,
                        'space-y-4 border-dashed text-sm text-white/75',
                    ]"
                >
                    <div>
                        <p class="flex items-center gap-2 font-medium text-amber-200">
                            <LogIn class="size-4 shrink-0" />
                            Déjà inscrit·e ?
                        </p>
                        <p class="mt-1 text-white/65">
                            Connecte-toi avec ton e-mail, sans attendre le lien
                            mail.
                        </p>
                    </div>
                    <Form
                        v-bind="PokerController.quickLogin.form()"
                        class="space-y-3"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="login-email" class="text-[#dcebe2]"
                                >Ton e-mail</Label
                            >
                            <Input
                                id="login-email"
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
                            variant="outline"
                            class="h-11 w-full border-white/15 bg-black/35 text-white/85 hover:bg-white/5 hover:text-white"
                            :disabled="processing"
                        >
                            <LogIn class="mr-2 size-4" />
                            {{
                                processing
                                    ? 'Connexion…'
                                    : 'Me connecter'
                            }}
                        </Button>
                    </Form>
                </div>
            </CardContent>
        </Card>

        <template v-else>
            <section
                class="flex flex-col gap-4 rounded-2xl border border-white/10 bg-black/45 px-4 py-4 backdrop-blur-md sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="min-w-0 flex-1">
                    <div
                        v-if="!isEditingProfile"
                        class="flex flex-wrap items-center gap-2"
                    >
                        <p class="text-sm text-white/85">
                            Salut
                            <strong class="text-amber-200">{{
                                participant.name
                            }}</strong
                            > !
                        </p>
                        <Button
                            type="button"
                            variant="ghost"
                            class="h-8 px-2 text-xs text-white/55 hover:bg-white/5 hover:text-white"
                            @click="isEditingProfile = true"
                        >
                            <Pencil class="mr-1 size-3.5" />
                            Modifier mon pseudo
                        </Button>
                    </div>
                    <Form
                        v-else
                        v-bind="PokerController.updateProfile.form()"
                        class="flex flex-col gap-2 sm:flex-row sm:items-end"
                        v-slot="{ errors, processing }"
                        @success="
                            () => {
                                isEditingProfile = false;
                                toast.success('Pseudo mis à jour');
                            }
                        "
                    >
                        <div class="grid min-w-0 flex-1 gap-1">
                            <Label for="profile-name" class="text-xs text-white/65"
                                >Ton pseudo</Label
                            >
                            <Input
                                id="profile-name"
                                name="name"
                                required
                                :default-value="participant.name"
                                autocomplete="nickname"
                                :class="pokerInput"
                            />
                            <InputError :message="errors.name" />
                        </div>
                        <div class="flex gap-2">
                            <Button
                                type="button"
                                variant="ghost"
                                class="h-11 text-white/60 hover:text-white"
                                :disabled="processing"
                                @click="isEditingProfile = false"
                            >
                                Annuler
                            </Button>
                            <Button
                                type="submit"
                                class="h-11 font-semibold"
                                :class="casinoChipPrimary"
                                :disabled="processing"
                            >
                                {{ processing ? 'Enregistrement…' : 'Enregistrer' }}
                            </Button>
                        </div>
                    </Form>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button
                        v-if="personalUrl"
                        type="button"
                        variant="outline"
                        class="h-11 border-white/10 bg-black/35 text-white/80 hover:bg-white/5 hover:text-white"
                        @click="copyPersonalLink"
                    >
                        <Copy class="mr-2 size-4" />
                        Copier mon lien
                    </Button>
                    <Form
                        v-bind="PokerController.resendAccessLink.form()"
                        v-slot="{ processing }"
                    >
                        <Button
                            type="submit"
                            variant="outline"
                            class="h-11 border-white/10 bg-black/35 text-white/80 hover:bg-white/5 hover:text-white"
                            :disabled="processing"
                        >
                            <RefreshCw
                                class="mr-2 size-4"
                                :class="{ 'animate-spin': processing }"
                            />
                            {{
                                processing ? 'Patience…' : 'Renvoyer mon lien'
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
                            class="h-11 text-[#8faa9a] hover:bg-white/5 hover:text-[#dcebe2]"
                            :disabled="processing"
                        >
                            <LogOut class="mr-2 size-4" />
                            Me déconnecter
                        </Button>
                    </Form>
                </div>
            </section>

            <PokerAdminPanel
                v-if="participant.isAdmin"
                :admin-participants="adminParticipants"
                :has-confirmed-dates="hasConfirmedDates"
                :current-participant-id="participant.id"
            />

            <nav
                v-if="hasConfirmedDates || showPollNav || showProposeNav"
                class="sticky top-0 z-20 -mx-1 flex flex-wrap gap-2 rounded-xl border border-white/10 bg-black/70 px-3 py-2 backdrop-blur-md"
                aria-label="Sections du sondage"
            >
                <Button
                    v-if="hasConfirmedDates"
                    type="button"
                    variant="ghost"
                    class="h-9 text-sm text-white/80 hover:text-white"
                    @click="scrollToSection('section-confirmed')"
                >
                    Calé
                </Button>
                <Button
                    v-if="showPollNav"
                    type="button"
                    variant="ghost"
                    class="h-9 text-sm text-white/80 hover:text-white"
                    @click="scrollToSection('section-poll')"
                >
                    Voter
                </Button>
                <Button
                    v-if="showProposeNav"
                    type="button"
                    variant="ghost"
                    class="h-9 text-sm text-white/80 hover:text-white"
                    @click="scrollToSection('section-propose')"
                >
                    Proposer
                </Button>
            </nav>

            <section
                v-if="hasConfirmedDates"
                id="section-confirmed"
                class="space-y-6 scroll-mt-24"
                aria-labelledby="heading-confirmed"
            >
                <h2 id="heading-confirmed" class="sr-only">
                    Soirées calées
                </h2>
                <PokerConfirmedCard
                    v-for="confirmedDate in round.confirmedDates"
                    :key="confirmedDate.id"
                    :confirmed-date="confirmedDate"
                    :participants="participants"
                    :edit-location-type="editLocationTypeFor(confirmedDate.id)"
                    @update:edit-location-type="
                        setEditLocationType(confirmedDate.id, $event)
                    "
                />
            </section>

            <Card
                v-if="isPolling"
                id="section-poll"
                :class="[pokerCard, 'scroll-mt-24']"
            >
                <CardHeader :class="pokerHeader">
                    <CardTitle
                        id="heading-poll"
                        class="font-serif text-xl text-white"
                    >
                        T’es dispo quand ?
                    </CardTitle>
                    <CardDescription :class="['text-sm', pokerMuted]">
                        <span aria-live="polite">{{ pollDescription }}</span>
                        <br />
                        <span>{{ pollThresholdDescription }}</span>
                        <br />
                        <span>{{ pollOpenParticipationDescription }}</span>
                        <template v-if="hasConfirmedDates">
                            <br />
                            <span
                                >D’autres sessions sont encore en cours de
                                planification.</span
                            >
                        </template>
                    </CardDescription>
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

                    <PokerPollDateCard
                        v-for="date in round.dates"
                        :key="date.id"
                        :date="date"
                        :threshold="round.minParticipants"
                        :participants="participants"
                        :selected-vote="selectedVotes[date.id]"
                        :is-submitting="voteSubmittingForDateId === date.id"
                        :is-deleting="dateDeletingId === date.id"
                        :is-editing-location="
                            editingLocationForDateId === date.id
                        "
                        :edit-location-type="editLocationTypeFor(date.id)"
                        @vote="setVote(date.id, $event)"
                        @delete="deleteProposedDate(date.id)"
                        @toggle-location-edit="toggleLocationEdit(date.id)"
                        @update:edit-location-type="
                            setEditLocationType(date.id, $event)
                        "
                        @location-edit-success="editingLocationForDateId = null"
                    />
                </CardContent>
            </Card>

            <Card
                v-if="isPolling"
                id="section-propose"
                :class="[pokerCard, 'scroll-mt-24']"
            >
                <CardHeader :class="pokerHeader">
                    <div
                        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <CardTitle
                                id="heading-propose"
                                class="font-serif text-xl text-white"
                            >
                                Proposer un créneau
                            </CardTitle>
                            <CardDescription :class="pokerMuted">
                                Propose un créneau, tu es alors responsable de
                                l'organisation :)
                            </CardDescription>
                        </div>
                        <Button
                            type="button"
                            class="h-11 w-full font-semibold sm:w-auto"
                            :class="casinoChipPrimary"
                            @click="isProposeFormOpen = !isProposeFormOpen"
                        >
                            <Plus
                                class="mr-2 size-4 transition-transform"
                                :class="{ 'rotate-45': isProposeFormOpen }"
                            />
                            {{
                                isProposeFormOpen
                                    ? 'Refermer'
                                    : 'Proposer une date'
                            }}
                        </Button>
                    </div>
                </CardHeader>
                <CardContent
                    v-if="isProposeFormOpen"
                    class="space-y-5 px-6 pt-6 pb-6"
                >
                    <Form
                        v-bind="PokerController.storeProposedDate.form()"
                        reset-on-success
                        class="space-y-5"
                        v-slot="{ errors, processing }"
                        @success="toast.success('Date ajoutée')"
                    >
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="date" class="text-[#dcebe2]"
                                    >Quel jour ?</Label
                                >
                                <Input
                                    id="date"
                                    type="date"
                                    name="date"
                                    required
                                    :min="minProposeDate"
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
                        </div>

                        <div class="grid gap-4 lg:grid-cols-[1fr_1fr]">
                            <div class="grid gap-2">
                                <Label
                                    for="location_type"
                                    class="text-[#dcebe2]"
                                    >Où ?</Label
                                >
                                <PokerLocationFields
                                    v-model:location-type="proposedLocationType"
                                    :participants="participants"
                                    :errors="errors"
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label for="theme" class="text-[#dcebe2]">
                                    Thème (optionnel)
                                </Label>
                                <Input
                                    id="theme"
                                    name="theme"
                                    maxlength="80"
                                    placeholder="Ex: soirée thématique"
                                    :class="pokerInput"
                                />
                                <InputError :message="errors.theme" />
                            </div>
                        </div>

                        <label
                            class="flex cursor-pointer items-center gap-3 rounded-xl border border-white/10 bg-black/35 px-4 py-3"
                        >
                            <input
                                type="hidden"
                                name="beginners_welcome"
                                value="0"
                            />
                            <input
                                id="beginners_welcome"
                                type="checkbox"
                                name="beginners_welcome"
                                value="1"
                                checked
                                class="size-5 shrink-0 rounded border-white/20 bg-black/50 text-amber-500 focus:ring-amber-400/40"
                            />
                            <span class="text-sm text-white/85">
                                <span class="font-medium text-white"
                                    >Débutant·e·s accepté·e·s</span
                                >
                                <span class="mt-0.5 block text-white/60">
                                    Indique que les novices sont les bienvenu·e·s
                                    sur ce créneau.
                                </span>
                            </span>
                        </label>

                        <div class="flex justify-end">
                            <Button
                                type="submit"
                                class="h-12 w-full font-semibold sm:w-auto"
                                :class="casinoChipPrimary"
                                :disabled="processing"
                            >
                                {{ processing ? 'Ajout…' : 'Ajouter' }}
                            </Button>
                        </div>
                    </Form>
                </CardContent>
            </Card>
        </template>
    </div>
</template>
