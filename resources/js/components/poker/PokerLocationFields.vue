<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { pokerInput } from '@/lib/pokerUi';

defineProps<{
    participants: { id: number; name: string }[];
    errors: Record<string, string | undefined>;
    idPrefix?: string;
}>();

const locationType = defineModel<string>('locationType', { default: 'mine' });
</script>

<template>
    <div class="space-y-3">
        <select
            :id="idPrefix ? `${idPrefix}-location_type` : 'location_type'"
            v-model="locationType"
            name="location_type"
            required
            :class="[pokerInput, 'rounded-md px-3']"
        >
            <option value="mine">Chez moi</option>
            <option value="member" :disabled="participants.length === 0">
                Chez un membre
            </option>
            <option value="fabrique">La fabrique</option>
            <option value="custom">Saisie libre</option>
        </select>
        <InputError :message="errors.location_type" />

        <select
            v-if="locationType === 'member'"
            name="location_participant_id"
            required
            :class="[pokerInput, 'rounded-md px-3']"
        >
            <option value="" disabled selected>Choisir un membre</option>
            <option
                v-for="member in participants"
                :key="member.id"
                :value="member.id"
            >
                Chez {{ member.name }}
            </option>
        </select>
        <InputError :message="errors.location_participant_id" />

        <Input
            v-if="locationType === 'custom'"
            name="location_custom"
            required
            maxlength="80"
            placeholder="Ex: Club house"
            :class="pokerInput"
        />
        <InputError :message="errors.location_custom" />
    </div>
</template>
