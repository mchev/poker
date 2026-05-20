<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PokerAmbience from '@/components/poker/PokerAmbience.vue';
</script>

<template>
    <div
        class="relative min-h-svh overflow-hidden text-[#f5f0e1] selection:bg-amber-400/30 selection:text-white"
    >
        <div
            class="pointer-events-none absolute inset-0 bg-[#062818] bg-cover bg-center bg-no-repeat"
            style="background-image: url('/images/poker/felt-bg.jpg')"
            aria-hidden="true"
        />
        <div
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(0,0,0,0)_0%,rgba(0,0,0,0.45)_100%)]"
            aria-hidden="true"
        />
        <div
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(212,175,55,0.12),transparent_55%)]"
            aria-hidden="true"
        />

        <PokerAmbience />

        <header
            class="relative border-b border-amber-500/25 bg-[#061f14]/75 backdrop-blur-md"
        >
            <div
                class="mx-auto flex max-w-3xl items-center gap-4 px-4 py-5 sm:px-6"
            >
                <div
                    class="flex size-12 shrink-0 items-center justify-center rounded-2xl border-2 border-amber-500/40 bg-[#0f5035] shadow-[inset_0_2px_0_rgba(255,255,255,0.1),0_4px_24px_rgba(0,0,0,0.5)]"
                    aria-hidden="true"
                >
                    <span class="text-2xl leading-none text-amber-300">♠</span>
                </div>
                <div class="min-w-0">
                    <Link
                        href="/"
                        class="font-serif text-2xl font-bold tracking-tight text-amber-100 transition-colors hover:text-amber-300"
                    >
                        Poker party
                    </Link>
                    <p class="mt-0.5 text-sm font-medium text-amber-200/80">
                        Viens te faire plumer !
                    </p>
                </div>
                <div
                    class="ml-auto hidden gap-1.5 text-xl sm:flex"
                    aria-hidden="true"
                >
                    <span class="text-[#c8dccf]">♣</span>
                    <span class="text-red-400/80">♥</span>
                    <span class="text-red-400/80">♦</span>
                    <span class="text-[#c8dccf]">♠</span>
                </div>
            </div>
        </header>

        <main class="relative z-10 mx-auto max-w-3xl px-4 py-8 sm:px-6">
            <slot />
        </main>

        <footer
            class="relative z-10 mx-auto max-w-3xl px-4 pb-8 text-center text-xs text-[#7a9a88] sm:px-6"
        >
            Garde ton lien reçu par e-mail pour revenir quand tu veux.
        </footer>
    </div>
</template>
