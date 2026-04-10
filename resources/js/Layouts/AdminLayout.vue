<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Toast from '@/Components/UI/Toast.vue';

const page = usePage();
const currentPath = computed(() => page.url);

const nav = [
    { label: 'Entradas',     href: '/admin/posts',    icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
    { label: 'Menú',         href: '/admin/menu',     icon: 'M4 6h16M4 12h16M4 18h16' },
    { label: 'Configuración',href: '/admin/settings', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z' },
];

</script>

<template>
    <div class="min-h-screen bg-[#060e20] text-white flex">
        <!-- Sidebar -->
        <aside class="w-60 flex-shrink-0 bg-[#050d1a] border-r border-white/5 flex flex-col">
            <!-- Logo -->
            <div class="p-5 border-b border-white/5">
                <Link href="/admin/posts" class="flex items-center gap-3">
                    <img src="/logo.svg" alt="Bynad" class="w-8 h-8 rounded-lg" />
                    <div>
                        <p class="text-sm font-bold text-white leading-none">Bynad Finance</p>
                        <p class="text-xs text-gray-500 mt-0.5">Panel Admin</p>
                    </div>
                </Link>
            </div>

            <!-- Nav -->
            <nav class="flex-1 p-3 space-y-0.5">
                <Link v-for="item in nav" :key="item.href" :href="item.href"
                    :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors', page.url.startsWith(item.href) ? 'bg-blue-500/15 text-blue-400 font-medium' : 'text-gray-400 hover:text-white hover:bg-white/5']">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="item.icon"/>
                    </svg>
                    {{ item.label }}
                </Link>
            </nav>

            <!-- Bottom -->
            <div class="p-3 border-t border-white/5 space-y-1">
                <Link href="/dashboard"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-500 hover:text-white hover:bg-white/5 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Volver a la app
                </Link>
                <Link href="/" target="_blank"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-500 hover:text-white hover:bg-white/5 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    Ver sitio
                </Link>
            </div>
        </aside>

        <!-- Content -->
        <div class="flex-1 flex flex-col min-h-screen">
            <main class="flex-1 p-8">
                <slot />
            </main>
        </div>

        <Toast />
    </div>
</template>
