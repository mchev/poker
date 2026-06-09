<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { CalendarPlus, Check, Mail, Pencil, Trash2, X } from 'lucide-vue-next';
import { computed } from 'vue';
import PokerController from '@/actions/App/Http/Controllers/PokerController';
import PokerLocationFields from '@/components/poker/PokerLocationFields.vue';
import PokerNameList from '@/components/poker/PokerNameList.vue';
import PokerThresholdProgress from '@/components/poker/PokerThresholdProgress.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    casinoChipNeutral,
    casinoChipPrimary,
    pokerPanel,
    voteOptionHint,
} from '@/lib/pokerUi';

type PollDate = {
    id: number;
    label: string;
    location: string | null;
    theme: string | null;
    beginnersWelcome: boolean;
    yesCount: number;
    maybeCount: number;
    yesNames: string[];
    maybeNames: string[];
    noNames: string[];
    reachedThreshold: boolean;
    myVote: string | null;
    canDelete: boolean;
    canEditLocation: boolean;
    canRemindNonVoters: boolean;
    nonVoterCount: number;
};

const props = defineProps<{
    date: PollDate;
    threshold: number;
    participants: { id: number; name: string }[];
    selectedVote: string | undefined;
    isSubmitting: boolean;
    isDeleting: boolean;
    isEditingLocation: boolean;
    editLocationType: string;
}>();

const emit = defineEmits<{
    vote: [vote: string];
    delete: [];
    toggleLocationEdit: [];
    'update:editLocationType': [value: string];
    locationEditSuccess: [];
}>();

const editLocationTypeModel = computed({
    get: () => props.editLocationType,
    set: (value: string) => emit('update:editLocationType', value),
});

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
    <div
        class="rounded-xl border p-4 transition-all"
        :class="
            date.reachedThreshold
                ? 'border-amber-400/35 bg-amber-500/5 shadow-[inset_0_0_0_1px_rgba(251,191,36,0.10)]'
                : pokerPanel
        "
    >
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <p class="font-semibold text-white">{{ date.label }}</p>
                <div
                    class="mt-2 flex flex-wrap items-center gap-2 text-sm text-white/70"
                >
                    <span v-if="date.location">{{ date.location }}</span>
                                    <Badge
                                        v-if="date.theme"
                                        class="border border-white/10 bg-white/5 text-white/75 hover:bg-white/5"
                                    >
                                        {{ date.theme }}
                                    </Badge>
                                    <Badge
                                        v-if="date.beginnersWelcome"
                                        class="border border-sky-400/30 bg-sky-500/10 text-sky-100 hover:bg-sky-500/10"
                                    >
                                        Débutant·e·s OK
                                    </Badge>
                                </div>
                <div class="mt-3 max-w-sm">
                    <PokerThresholdProgress
                        :yes-count="date.yesCount"
                        :threshold="threshold"
                    />
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Badge
                    v-if="date.reachedThreshold"
                    class="border border-amber-400/30 bg-amber-500/10 text-amber-100 hover:bg-amber-500/10"
                >
                    Seuil atteint
                </Badge>
                <Button
                    v-if="date.canEditLocation"
                    type="button"
                    variant="ghost"
                    class="h-9 border border-white/10 bg-black/30 px-3 text-white/60 hover:bg-white/5 hover:text-white"
                    @click="emit('toggleLocationEdit')"
                >
                    <Pencil class="mr-1.5 size-4" />
                    {{ isEditingLocation ? 'Annuler' : 'Lieu' }}
                </Button>
                <Form
                    v-if="date.canRemindNonVoters"
                    v-bind="PokerController.remindNonVoters.form(date.id)"
                    class="contents"
                    v-slot="{ processing }"
                >
                    <Button
                        type="submit"
                        variant="ghost"
                        class="h-9 border border-white/10 bg-black/30 px-3 text-white/60 hover:bg-sky-500/10 hover:text-sky-100"
                        :disabled="processing"
                    >
                        <Mail class="mr-1.5 size-4" />
                        Relancer ({{ date.nonVoterCount }})
                    </Button>
                </Form>
                <Button
                    v-if="date.canDelete"
                    type="button"
                    variant="ghost"
                    class="h-9 border border-white/10 bg-black/30 px-3 text-white/60 hover:bg-rose-500/10 hover:text-rose-100"
                    :disabled="isDeleting"
                    @click="emit('delete')"
                >
                    <Trash2 class="mr-1.5 size-4" />
                    Supprimer
                </Button>
            </div>
        </div>

        <Form
            v-if="date.canEditLocation && isEditingLocation"
            v-bind="PokerController.updateProposedDate.form(date.id)"
            class="mb-4 space-y-3 rounded-xl border border-white/10 bg-black/35 p-4"
            v-slot="{ errors, processing }"
            @success="emit('locationEditSuccess')"
        >
            <PokerLocationFields
                v-model:location-type="editLocationTypeModel"
                :participants="participants"
                :errors="errors"
                :id-prefix="`poll-${date.id}`"
            />
            <Button
                type="submit"
                class="h-11 font-semibold"
                :class="casinoChipPrimary"
                :disabled="processing"
            >
                Enregistrer le lieu
            </Button>
        </Form>

        <div class="mb-4 grid gap-2 text-sm sm:grid-cols-3">
            <PokerNameList
                :names="date.yesNames"
                label="Partants"
                label-class="text-amber-300/90"
            />
            <PokerNameList
                :names="date.maybeNames"
                label="Peut-être"
                label-class="text-white/65"
            />
            <PokerNameList
                :names="date.noNames"
                label="Pas possible"
                label-class="text-stone-400"
            />
        </div>

        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
            <div v-for="option in voteOptions" :key="option.value" class="grid gap-1">
                <Button
                    type="button"
                    variant="outline"
                    :class="[
                        'h-12 justify-center text-base transition-transform active:translate-y-px active:scale-[0.99]',
                        casinoChipNeutral,
                        selectedVote === option.value
                            ? option.activeClass
                            : 'hover:bg-white/5',
                    ]"
                    :aria-pressed="selectedVote === option.value"
                    :disabled="isSubmitting"
                    @click="emit('vote', option.value)"
                >
                    <component :is="option.icon" class="mr-2 size-4" />
                    {{ option.label }}
                </Button>
                <p
                    v-if="option.value === 'maybe'"
                    class="text-center text-xs text-white/55"
                >
                    {{ voteOptionHint.maybe }}
                </p>
            </div>
        </div>
    </div>
</template>
