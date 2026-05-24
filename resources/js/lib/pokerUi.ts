export const pokerCard =
    'gap-0 overflow-hidden border border-white/10 bg-black/55 py-0 text-white shadow-[0_18px_54px_rgba(0,0,0,0.75),inset_0_1px_0_rgba(255,255,255,0.06)] backdrop-blur-md';

export const pokerHeader =
    'gap-3 border-b border-white/10 bg-black/40 px-6 pt-6 pb-5';

export const pokerMuted = 'text-white/70';

export const pokerInput =
    'h-12 border-white/10 bg-black/40 text-base text-white placeholder:text-white/40 focus-visible:border-amber-400/45 focus-visible:ring-amber-400/20';

export const pokerPanel =
    'rounded-xl border border-white/10 bg-black/35 p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.05)]';

export const casinoChipPrimary =
    '!relative !rounded-xl !border !border-amber-400/35 !bg-black/55 !bg-[linear-gradient(180deg,rgba(255,255,255,0.10)_0%,rgba(255,255,255,0.04)_45%,rgba(0,0,0,0.35)_100%)] !px-4 !text-amber-50 shadow-[0_14px_40px_rgba(0,0,0,0.75),0_0_0_1px_rgba(251,191,36,0.10),inset_0_1px_0_rgba(255,255,255,0.10)] backdrop-blur-md hover:!bg-[linear-gradient(180deg,rgba(255,255,255,0.14)_0%,rgba(255,255,255,0.06)_45%,rgba(0,0,0,0.40)_100%)] focus-visible:ring-2 focus-visible:ring-amber-400/40';

export const casinoChipNeutral =
    '!relative !rounded-xl !border !border-white/15 !bg-black/45 !bg-[linear-gradient(180deg,rgba(255,255,255,0.10)_0%,rgba(255,255,255,0.04)_45%,rgba(0,0,0,0.35)_100%)] !px-4 !text-white/90 shadow-[0_14px_40px_rgba(0,0,0,0.75),inset_0_1px_0_rgba(255,255,255,0.10)] backdrop-blur-md hover:!bg-[linear-gradient(180deg,rgba(255,255,255,0.14)_0%,rgba(255,255,255,0.06)_45%,rgba(0,0,0,0.40)_100%)] focus-visible:ring-2 focus-visible:ring-white/20';

export function formatNamesList(
    names: string[],
    maxVisible = 4,
): { visible: string; extra: number } {
    if (names.length === 0) {
        return { visible: '—', extra: 0 };
    }

    if (names.length <= maxVisible) {
        return { visible: names.join(', '), extra: 0 };
    }

    return {
        visible: names.slice(0, maxVisible).join(', '),
        extra: names.length - maxVisible,
    };
}

export function daysUntilLabel(startsAt: string): string {
    const target = new Date(startsAt);
    const now = new Date();
    const diffMs = target.getTime() - now.getTime();
    const diffDays = Math.round(diffMs / (1000 * 60 * 60 * 24));

    if (diffDays < 0) {
        return 'C’est passé';
    }

    if (diffDays === 0) {
        return "C'est aujourd'hui";
    }

    if (diffDays === 1) {
        return 'Demain';
    }

    return `Dans ${diffDays} jours`;
}

export function minDateForInput(): string {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    tomorrow.setHours(0, 0, 0, 0);

    return tomorrow.toISOString().split('T')[0] ?? '';
}

export const voteOptionHint: Record<string, string> = {
    maybe: 'Ne compte pas pour caler la soirée.',
};

export const myVoteLabels: Record<string, string> = {
    yes: 'Tu viens',
    no: 'Pas cette fois',
    maybe: 'Peut-être',
};
