<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { CalendarPlus, Download, MapPin, PartyPopper, Pencil, StickyNote } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import PokerController from '@/actions/App/Http/Controllers/PokerController';
import InputError from '@/components/InputError.vue';
import PokerLocationFields from '@/components/poker/PokerLocationFields.vue';
import PokerNameList from '@/components/poker/PokerNameList.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    casinoChipPrimary,
    daysUntilLabel,
    formatGameList,
    myVoteLabels,
    pokerCard,
    pokerHeader,
    pokerInput,
    pokerMuted,
} from '@/lib/pokerUi';

type Game = {
    id: number;
    name: string;
    slug: string;
    icon: string | null;
};

type ConfirmedDate = {
    id: number;
    startsAt: string;
    label: string;
    location: string | null;
    theme: string | null;
    beginnersWelcome: boolean;
    games: Game[];
    note: string | null;
    myVote: 'yes' | 'no' | 'maybe' | null;
    attendingCount: number;
    attendingNames: string[];
    canEditLocation: boolean;
    canEditNote: boolean;
    calendarIcsUrl: string;
    googleCalendarUrl: string;
};

const props = defineProps<{
    confirmedDate: ConfirmedDate;
    participants: { id: number; name: string }[];
    editLocationType: string;
}>();

const emit = defineEmits<{
    'update:editLocationType': [value: string];
}>();

const showEditPanel = ref(false);

const participantsBadgeLabel = computed(() => {
    const count = props.confirmedDate.attendingCount;

    return count > 1 ? `${count} participants` : `${count} participant`;
});

const editLocationTypeModel = computed({
    get: () => props.editLocationType,
    set: (value: string) => emit('update:editLocationType', value),
});
</script>

<template>
    <Card :class="pokerCard">
        <CardHeader :class="pokerHeader">
            <div class="flex flex-wrap items-center gap-2">
                <Badge
                    class="w-fit border border-amber-400/30 bg-amber-500/10 px-3 py-1 text-sm font-semibold text-amber-100 hover:bg-amber-500/10"
                >
                    <PartyPopper class="mr-1.5 inline size-4" />
                    C’est calé !
                </Badge>
                <Badge
                    class="border border-white/15 bg-white/5 text-white/80 hover:bg-white/5"
                >
                    {{ daysUntilLabel(confirmedDate.startsAt) }}
                </Badge>
                <Badge
                    v-if="confirmedDate.beginnersWelcome"
                    class="border border-sky-400/30 bg-sky-500/10 text-sky-100 hover:bg-sky-500/10"
                >
                    Débutant·e·s OK
                </Badge>
                <Badge
                    class="border border-emerald-400/30 bg-emerald-500/10 text-emerald-100 hover:bg-emerald-500/10"
                >
                    {{ participantsBadgeLabel }}
                </Badge>
                <Badge
                    v-if="confirmedDate.myVote"
                    class="border border-emerald-400/30 bg-emerald-500/10 text-emerald-100 hover:bg-emerald-500/10"
                >
                    {{ myVoteLabels[confirmedDate.myVote] ?? confirmedDate.myVote }}
                </Badge>
            </div>
            <CardTitle class="font-serif text-2xl text-white">
                ♠ Soirée{{ confirmedDate.games.length > 0 ? ' ' + formatGameList(confirmedDate.games) : '' }}
                {{ confirmedDate.label }}{{ confirmedDate.location ? ' — ' + confirmedDate.location : '' }}
            </CardTitle>
            <p
                v-if="confirmedDate.note"
                class="mt-2 rounded-lg border border-white/10 bg-black/35 px-3 py-2 text-sm text-white/85"
            >
                {{ confirmedDate.note }}
            </p>
        </CardHeader>
        <CardContent class="space-y-4 px-6 pt-6 pb-6">
            <div class="flex flex-wrap gap-2">
                <Button
                    as-child
                    variant="outline"
                    class="h-11 border-white/10 bg-black/35 text-white/85 hover:bg-white/5 hover:text-white"
                >
                    <a
                        :href="confirmedDate.calendarIcsUrl"
                        download
                        class="inline-flex items-center"
                    >
                        <Download class="mr-2 size-4" />
                        Fichier .ics
                    </a>
                </Button>
                <Button
                    as-child
                    variant="outline"
                    class="h-11 border-white/10 bg-black/35 text-white/85 hover:bg-white/5 hover:text-white"
                >
                    <a
                        :href="confirmedDate.googleCalendarUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center"
                    >
                        <CalendarPlus class="mr-2 size-4" />
                        Google Agenda
                    </a>
                </Button>
            </div>

            <div
                v-if="confirmedDate.canEditLocation || confirmedDate.canEditNote"
                class="flex flex-wrap gap-2"
            >
                <Button
                    v-if="confirmedDate.canEditLocation || confirmedDate.canEditNote"
                    type="button"
                    variant="ghost"
                    class="h-9 border border-white/10 bg-black/30 px-3 text-white/70 hover:bg-white/5 hover:text-white"
                    @click="showEditPanel = !showEditPanel"
                >
                    <Pencil class="mr-1.5 size-4" />
                    {{ showEditPanel ? 'Fermer' : 'Modifier' }}
                </Button>
            </div>

            <div v-if="showEditPanel" class="space-y-4">
                <Form
                    v-if="confirmedDate.canEditLocation"
                    v-bind="
                        PokerController.updateProposedDate.form(
                            confirmedDate.id,
                        )
                    "
                    class="space-y-3 rounded-xl border border-white/10 bg-black/35 p-4"
                    v-slot="{ errors, processing }"
                    @success="showEditPanel = false"
                >
                    <div class="flex items-center gap-2 text-amber-300">
                        <MapPin class="size-4" />
                        <p class="text-sm font-semibold">Modifier le lieu</p>
                    </div>
                    <PokerLocationFields
                        v-model:location-type="editLocationTypeModel"
                        :participants="participants"
                        :errors="errors"
                        :id-prefix="`confirmed-${confirmedDate.id}`"
                    />
                    <Button
                        type="submit"
                        variant="outline"
                        class="h-11 border-white/10 bg-black/40 text-white/85"
                        :disabled="processing"
                    >
                        Enregistrer le lieu
                    </Button>
                </Form>

                <Form
                    v-if="confirmedDate.canEditNote"
                    v-bind="
                        PokerController.updateProposedDate.form(
                            confirmedDate.id,
                        )
                    "
                    class="space-y-3 rounded-xl border border-white/10 bg-black/35 p-4"
                    v-slot="{ errors, processing }"
                >
                    <div class="flex items-center gap-2 text-amber-300">
                        <StickyNote class="size-4" />
                        <Label
                            :for="`note-${confirmedDate.id}`"
                            class="text-sm font-semibold text-amber-300"
                        >
                            Note pour les participants
                        </Label>
                    </div>
                    <textarea
                        :id="`note-${confirmedDate.id}`"
                        name="note"
                        rows="3"
                        maxlength="500"
                        :class="[
                            pokerInput,
                            'min-h-24 resize-y rounded-md px-3 py-2',
                        ]"
                        :value="confirmedDate.note ?? ''"
                        placeholder="Ex: Apporter des chips…"
                    />
                    <InputError :message="errors.note" />
                    <Button
                        type="submit"
                        variant="outline"
                        class="h-11 border-white/10 bg-black/40 text-white/85"
                        :disabled="processing"
                    >
                        Enregistrer la note
                    </Button>
                </Form>
            </div>

            <PokerNameList
                :names="confirmedDate.attendingNames"
                label="Participants"
                label-class="text-amber-300/90"
                :max-visible="999"
            />

            <div class="flex flex-col gap-3 sm:flex-row">
                <Form
                    v-bind="PokerController.storeAttendance.form()"
                    v-slot="{ processing }"
                    class="flex-1"
                    @success="showEditPanel = false"
                >
                    <input
                        type="hidden"
                        name="proposed_date_id"
                        :value="confirmedDate.id"
                    />
                    <input type="hidden" name="attending" value="yes" />
                    <Button
                        type="submit"
                        class="h-12 w-full text-base font-semibold"
                        :class="[
                            casinoChipPrimary,
                            confirmedDate.myVote === 'yes'
                                ? 'ring-2 ring-emerald-400/50'
                                : '',
                        ]"
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
                    <input
                        type="hidden"
                        name="proposed_date_id"
                        :value="confirmedDate.id"
                    />
                    <input type="hidden" name="attending" value="no" />
                    <Button
                        type="submit"
                        variant="outline"
                        class="h-12 w-full border-white/10 bg-black/40 text-base text-white/85 hover:bg-white/5 hover:text-white"
                        :class="
                            confirmedDate.myVote === 'no'
                                ? 'ring-2 ring-rose-400/40'
                                : ''
                        "
                        :disabled="processing"
                    >
                        Pas cette fois
                    </Button>
                </Form>
            </div>
        </CardContent>
    </Card>
</template>
