<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { History } from 'lucide-vue-next';
import { Toaster } from '@/components/ui/sonner';
import { history } from '@/routes/poker';
</script>

<template>
    <div
        class="relative min-h-svh overflow-hidden text-[#f5f0e1] selection:bg-amber-400/30 selection:text-white"
    >
        <div
            class="pointer-events-none absolute inset-0 bg-black bg-auto bg-right bg-no-repeat"
            style="background-image: url('/images/poker/background.webp')"
            aria-hidden="true"
        />
        <div
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(0,0,0,0)_0%,rgba(0,0,0,0.65)_100%)]"
            aria-hidden="true"
        />
        <div
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(251,191,36,0.10),transparent_60%)]"
            aria-hidden="true"
        />

        <header
            class="relative border-b border-white/10 bg-black/55 backdrop-blur-md"
        >
            <div
                class="mx-auto flex max-w-3xl items-center gap-4 px-4 py-5 sm:px-6"
            >
                <div
                    class="flex size-12 shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-black/40 shadow-[inset_0_1px_0_rgba(255,255,255,0.08),0_18px_48px_rgba(0,0,0,0.75)]"
                    aria-hidden="true"
                >
                    <span class="text-2xl leading-none text-amber-300">♠</span>
                </div>
                <div class="min-w-0 flex-1">
                    <Link
                        href="/"
                        class="font-serif text-2xl font-bold tracking-tight text-white transition-colors hover:text-amber-200"
                    >
                        Poker party
                    </Link>
                    <p class="mt-0.5 text-sm font-medium text-white/70">
                        Viens te faire plumer !
                    </p>
                </div>
                <Link
                    :href="history.url()"
                    class="inline-flex h-11 shrink-0 items-center gap-2 rounded-xl border border-white/10 bg-black/35 px-3 text-sm font-medium text-amber-300/90 transition-colors hover:bg-white/5 hover:text-amber-200"
                >
                    <History class="size-4" />
                    <span class="hidden sm:inline">Historique</span>
                </Link>
                <div
                    class="hidden gap-1.5 text-xl sm:flex"
                    aria-hidden="true"
                >
                    <span class="text-[#c8dccf]">♣</span>
                    <span class="text-amber-300/80">♥</span>
                    <span class="text-amber-300/80">♦</span>
                    <span class="text-[#c8dccf]">♠</span>
                </div>
            </div>
        </header>

        <main class="relative z-10 mx-auto max-w-3xl px-4 py-8 sm:px-6">
            <slot />
        </main>

        <footer
            class="relative z-10 mx-auto max-w-3xl px-4 pb-8 text-center text-xs text-white/50 sm:px-6"
        >
            <p>
                Garde ton lien reçu par e-mail pour revenir quand tu veux.
            </p>
        </footer>

        <Toaster rich-colors close-button position="top-center" />
    </div>
</template>
