<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const scrolled = ref(false);
const mobileOpen = ref(false);

const onScroll = () => { scrolled.value = window.scrollY > 20; };
onMounted(() => window.addEventListener('scroll', onScroll));
onUnmounted(() => window.removeEventListener('scroll', onScroll));

const page = usePage();
const isAuth = page.props.auth?.user;
const menuItems = computed(() => page.props.menuItems ?? []);
</script>

<template>
    <div class="min-h-screen bg-[#050d1f] text-white">
        <!-- Navbar -->
        <header :class="['fixed top-0 left-0 right-0 z-50 transition-all duration-300', scrolled ? 'bg-[#050d1f]/90 backdrop-blur-xl border-b border-white/5 shadow-xl' : 'bg-transparent']">
            <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
                <!-- Logo -->
                <Link href="/" class="flex items-center gap-3">
                    <img src="/logo.svg" alt="Bynad" class="w-9 h-9 rounded-xl" />
                    <span class="text-xl font-bold bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent">Bynad Finance</span>
                </Link>

                <!-- Desktop nav -->
                <nav class="hidden md:flex items-center gap-8">
                    <template v-for="item in menuItems" :key="item.id">
                        <a v-if="item.url.startsWith('http') || item.url.startsWith('#') || item.url.includes('#')"
                            :href="item.url" :target="item.target"
                            class="text-sm text-gray-400 hover:text-white transition-colors">{{ item.label }}</a>
                        <Link v-else :href="item.url" :target="item.target"
                            class="text-sm text-gray-400 hover:text-white transition-colors">{{ item.label }}</Link>
                    </template>
                </nav>

                <!-- Auth buttons -->
                <div class="hidden md:flex items-center gap-3">
                    <template v-if="isAuth">
                        <Link :href="route('dashboard')" class="px-5 py-2 text-sm font-medium bg-gradient-to-r from-blue-500 to-cyan-500 rounded-xl text-white hover:shadow-lg hover:shadow-blue-500/25 transition-all">
                            Ir al panel
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="px-5 py-2 text-sm font-medium text-gray-300 hover:text-white transition-colors">
                            Iniciar sesion
                        </Link>
                        <Link :href="route('register')" class="px-5 py-2 text-sm font-medium bg-gradient-to-r from-blue-500 to-cyan-500 rounded-xl text-white hover:shadow-lg hover:shadow-blue-500/25 transition-all">
                            Registrarse
                        </Link>
                    </template>
                </div>

                <!-- Mobile toggle -->
                <button @click="mobileOpen = !mobileOpen" class="md:hidden text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path v-if="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Mobile menu -->
            <div v-if="mobileOpen" class="md:hidden bg-[#050d1f]/95 backdrop-blur-xl border-t border-white/5 px-6 py-4 space-y-3">
                <template v-for="item in menuItems" :key="item.id">
                    <a v-if="item.url.startsWith('http') || item.url.includes('#')"
                        :href="item.url" :target="item.target"
                        class="block text-sm text-gray-400 hover:text-white py-2">{{ item.label }}</a>
                    <Link v-else :href="item.url"
                        class="block text-sm text-gray-400 hover:text-white py-2">{{ item.label }}</Link>
                </template>
                <div class="pt-3 border-t border-white/10 flex flex-col gap-2">
                    <Link v-if="!isAuth" :href="route('login')" class="text-center py-2.5 text-sm text-gray-300 border border-white/10 rounded-xl hover:border-white/30">Iniciar sesion</Link>
                    <Link :href="isAuth ? route('dashboard') : route('register')" class="text-center py-2.5 text-sm font-medium bg-gradient-to-r from-blue-500 to-cyan-500 rounded-xl">
                        {{ isAuth ? 'Ir al panel' : 'Registrarse' }}
                    </Link>
                </div>
            </div>
        </header>

        <!-- Content -->
        <slot />

        <!-- Footer -->
        <footer class="border-t border-white/5 bg-[#030810]">
            <div class="max-w-7xl mx-auto px-6 py-12">
                <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="flex items-center gap-3">
                        <img src="/logo.svg" alt="Bynad" class="w-8 h-8 rounded-lg" />
                        <span class="font-bold bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent">Bynad Finance</span>
                    </div>
                    <nav class="flex items-center gap-6">
                        <Link href="/" class="text-sm text-gray-500 hover:text-gray-300 transition-colors">Inicio</Link>
                        <Link href="/blog" class="text-sm text-gray-500 hover:text-gray-300 transition-colors">Blog</Link>
                        <Link :href="route('login')" class="text-sm text-gray-500 hover:text-gray-300 transition-colors">Login</Link>
                    </nav>
                    <p class="text-xs text-gray-600">© {{ new Date().getFullYear() }} Bynad Finance. Todos los derechos reservados.</p>
                </div>
            </div>
        </footer>
    </div>
</template>
