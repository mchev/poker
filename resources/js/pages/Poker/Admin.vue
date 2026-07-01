<script setup lang="ts">
import { Form, Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Gamepad2, Mail, Plus, RefreshCw, Shield, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import PokerController from '@/actions/App/Http/Controllers/PokerController';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    casinoChipPrimary,
    pokerCard,
    pokerHeader,
    pokerMuted,
    pokerPanel,
} from '@/lib/pokerUi';
import { home } from '@/routes';

type AdminParticipant = {
    id: number;
    name: string;
    email: string;
};

type Game = {
    id: number;
    name: string;
    slug: string;
    icon: string | null;
};

const props = defineProps<{
    participant: { id: number; name: string; isAdmin: boolean } | null;
    adminParticipants: AdminParticipant[];
    availableGames: Game[];
    hasConfirmedDates: boolean;
    subscribedCount: number;
}>();

const deletingId = ref<number | null>(null);

function deleteParticipant(participant: AdminParticipant): void {
    if (
        !window.confirm(
            `Retirer ${participant.name} (${participant.email}) ? Ses votes seront supprimés.`,
        )
    ) {
        return;
    }

    router.delete(
        PokerController.adminDestroyParticipant.url(participant.id),
        {
            preserveScroll: true,
            onStart: () => {
                deletingId.value = participant.id;
            },
            onSuccess: () => {
                toast.success('Joueur retiré');
            },
            onFinish: () => {
                deletingId.value = null;
            },
        },
    );
}

const gameForm = useForm({
    name: '',
    slug: '',
    icon: '',
});
</script>

<template>
    <Head title="Administration" />

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
                    <Shield class="size-5" />
                    <span class="text-sm font-semibold uppercase tracking-wider"
                        >Admin</span
                    >
                </div>
                <CardTitle class="font-serif text-2xl text-white">
                    Gestion des joueurs
                </CardTitle>
                <CardDescription :class="['text-base', pokerMuted]">
                    {{ subscribedCount }} inscrit{{ subscribedCount > 1 ? 's' : '' }}
                    — Actions réservées à l’administrateur.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4 px-6 pt-6 pb-6">
                <Form
                    v-if="hasConfirmedDates"
                    v-bind="PokerController.adminResendConfirmationToAll.form()"
                    v-slot="{ processing }"
                >
                    <Button
                        type="submit"
                        class="h-11 w-full font-semibold sm:w-auto"
                        :class="casinoChipPrimary"
                        :disabled="processing"
                    >
                        <Mail class="mr-2 size-4" />
                        {{
                            processing
                                ? 'Envoi…'
                                : 'Renvoyer « c’est calé » à tout le monde'
                        }}
                    </Button>
                </Form>

                <div class="space-y-2">
                    <div
                        v-for="listed in adminParticipants"
                        :key="listed.id"
                        :class="[
                            pokerPanel,
                            'flex flex-col gap-3 p-3 sm:flex-row sm:items-center sm:justify-between',
                        ]"
                    >
                        <div class="min-w-0">
                            <p class="font-medium text-white">
                                {{ listed.name }}
                                <span
                                    v-if="listed.id === participant?.id"
                                    class="text-xs font-normal text-amber-200/90"
                                >
                                    (toi)
                                </span>
                            </p>
                            <p class="truncate text-sm text-white/55">
                                {{ listed.email }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Form
                                v-if="hasConfirmedDates"
                                v-bind="
                                    PokerController.adminResendConfirmationToParticipant.form(
                                        listed.id,
                                    )
                                "
                                v-slot="{ processing }"
                            >
                                <Button
                                    type="submit"
                                    variant="outline"
                                    class="h-9 border-white/10 bg-black/35 px-3 text-xs text-white/80 hover:bg-white/5 hover:text-white"
                                    :disabled="processing"
                                >
                                    <Mail class="mr-1.5 size-3.5" />
                                    C’est calé
                                </Button>
                            </Form>
                            <Form
                                v-bind="
                                    PokerController.adminResendAccessLinkToParticipant.form(
                                        listed.id,
                                    )
                                "
                                v-slot="{ processing }"
                            >
                                <Button
                                    type="submit"
                                    variant="outline"
                                    class="h-9 border-white/10 bg-black/35 px-3 text-xs text-white/80 hover:bg-white/5 hover:text-white"
                                    :disabled="processing"
                                >
                                    <RefreshCw
                                        class="mr-1.5 size-3.5"
                                        :class="{ 'animate-spin': processing }"
                                    />
                                    Lien
                                </Button>
                            </Form>
                            <Button
                                type="button"
                                variant="ghost"
                                class="h-9 border border-white/10 bg-black/30 px-3 text-xs text-white/60 hover:bg-rose-500/10 hover:text-rose-100"
                                :disabled="deletingId === listed.id"
                                @click="deleteParticipant(listed)"
                            >
                                <Trash2 class="mr-1.5 size-3.5" />
                                Retirer
                            </Button>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card :class="pokerCard">
            <CardHeader :class="pokerHeader">
                <div class="flex items-center gap-2 text-sky-300">
                    <Gamepad2 class="size-5" />
                    <span class="text-sm font-semibold uppercase tracking-wider"
                        >Jeux</span
                    >
                </div>
                <CardTitle class="font-serif text-2xl text-white">
                    Gestion des jeux
                </CardTitle>
                <CardDescription :class="['text-base', pokerMuted]">
                    {{ availableGames.length }} jeu{{ availableGames.length > 1 ? 'x' : '' }}
                    disponibles — ajoutez-en de nouveaux.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4 px-6 pt-6 pb-6">
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="game in availableGames"
                        :key="game.id"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-white/10 bg-black/30 px-3 py-1.5 text-sm text-white/80"
                    >
                        <span v-if="game.icon">{{ game.icon }}</span>
                        {{ game.name }}
                        <span class="text-[11px] text-white/40">({{ game.slug }})</span>
                    </span>
                </div>

                <div class="border-t border-white/5 pt-4">
                    <p class="mb-3 text-sm font-medium text-white/75">
                        Ajouter un jeu
                    </p>
                    <form
                        @submit.prevent="
                            gameForm.post(PokerController.adminStoreGame.url(), {
                                preserveScroll: true,
                                onSuccess: () => {
                                    gameForm.reset();
                                    toast.success('Jeu ajouté');
                                },
                            })
                        "
                        class="flex flex-wrap items-end gap-3"
                    >
                        <div class="space-y-1.5">
                            <Label
                                for="game-name"
                                class="text-xs text-white/60"
                            >
                                Nom
                            </Label>
                            <Input
                                id="game-name"
                                v-model="gameForm.name"
                                type="text"
                                placeholder="Poker"
                                class="h-9 border-white/10 bg-black/30 text-sm text-white placeholder:text-white/30"
                                required
                            />
                            <p
                                v-if="gameForm.errors.name"
                                class="text-xs text-rose-300"
                            >
                                {{ gameForm.errors.name }}
                            </p>
                        </div>
                        <div class="space-y-1.5">
                            <Label
                                for="game-slug"
                                class="text-xs text-white/60"
                            >
                                Slug
                            </Label>
                            <Input
                                id="game-slug"
                                v-model="gameForm.slug"
                                type="text"
                                placeholder="poker"
                                class="h-9 border-white/10 bg-black/30 text-sm text-white placeholder:text-white/30"
                                required
                            />
                            <p
                                v-if="gameForm.errors.slug"
                                class="text-xs text-rose-300"
                            >
                                {{ gameForm.errors.slug }}
                            </p>
                        </div>
                        <div class="space-y-1.5">
                            <Label
                                for="game-icon"
                                class="text-xs text-white/60"
                            >
                                Icône
                            </Label>
                            <Input
                                id="game-icon"
                                v-model="gameForm.icon"
                                type="text"
                                placeholder="🃏"
                                class="h-9 w-16 border-white/10 bg-black/30 text-sm text-white placeholder:text-white/30"
                            />
                        </div>
                        <Button
                            type="submit"
                            variant="outline"
                            class="h-9 border-white/10 bg-black/35 px-3 text-xs text-white/80 hover:bg-white/5 hover:text-white"
                            :disabled="gameForm.processing"
                        >
                            <Plus class="mr-1.5 size-3.5" />
                            Ajouter
                        </Button>
                    </form>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
