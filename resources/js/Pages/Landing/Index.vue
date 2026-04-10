<script setup>
import LandingLayout from '@/Layouts/LandingLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

defineProps({ posts: Array });

// Animated counters
const counts = ref({ families: 0, expenses: 0, savings: 0 });
const targets = { families: 500, expenses: 50000, savings: 98 };

onMounted(() => {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounters();
                observer.disconnect();
            }
        });
    });
    const el = document.getElementById('stats-section');
    if (el) observer.observe(el);
});

function animateCounters() {
    Object.keys(targets).forEach(key => {
        const target = targets[key];
        const duration = 2000;
        const step = target / (duration / 16);
        let current = 0;
        const timer = setInterval(() => {
            current = Math.min(current + step, target);
            counts.value[key] = Math.floor(current);
            if (current >= target) clearInterval(timer);
        }, 16);
    });
}

const features = [
    {
        icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        title: 'Dashboard en tiempo real',
        desc: 'Visualiza toda tu situacion financiera familiar en graficos claros y actualizados al instante.',
        color: 'from-blue-500 to-cyan-500',
    },
    {
        icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        title: 'Cuentas y tarjetas',
        desc: 'Administra bancos, billeteras virtuales y tarjetas de credito de todos los miembros.',
        color: 'from-violet-500 to-purple-600',
    },
    {
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        title: 'Gastos fijos y variables',
        desc: 'Controla lo que gasta cada miembro. Presupuestos, categorias y alertas automaticas.',
        color: 'from-rose-500 to-pink-600',
    },
    {
        icon: 'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        title: 'Ingresos compartidos',
        desc: 'Registra ingresos por persona y fuente. Evolucion mensual y anual con graficos detallados.',
        color: 'from-emerald-500 to-teal-600',
    },
    {
        icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5',
        title: 'Patrimonio familiar',
        desc: 'Tus activos en pesos y dolares con conversion automatica. Inmuebles, crypto, inversiones.',
        color: 'from-amber-500 to-orange-600',
    },
    {
        icon: 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z',
        title: 'Control de supermercado',
        desc: 'Registra cada compra con productos y precios. Seguimiento de gastos del hogar mes a mes.',
        color: 'from-sky-500 to-blue-600',
    },
];

const steps = [
    { n: '01', title: 'Crea tu familia', desc: 'Registrate y se crea automaticamente tu grupo familiar. Comparte el codigo con tu pareja o familia.' },
    { n: '02', title: 'Carguen sus datos', desc: 'Cada miembro carga sus cuentas, tarjetas e ingresos. Todo queda visible para el grupo.' },
    { n: '03', title: 'Tomen decisiones juntos', desc: 'El dashboard muestra la situacion real de la familia para que puedan planificar con informacion.' },
];
</script>

<template>
    <Head title="Bynad Finance - Finanzas familiares inteligentes" />
    <LandingLayout>

        <!-- ═══ HERO ═══ -->
        <section class="relative min-h-screen flex items-center overflow-hidden">
            <!-- Background effects -->
            <div class="absolute inset-0">
                <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute bottom-1/4 right-1/4 w-80 h-80 bg-cyan-500/15 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s"></div>
                <div class="absolute top-1/2 left-1/2 w-64 h-64 bg-violet-600/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s"></div>
            </div>

            <!-- Grid pattern -->
            <div class="absolute inset-0 opacity-5" style="background-image: linear-gradient(rgba(255,255,255,.1) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.1) 1px, transparent 1px); background-size: 60px 60px;"></div>

            <div class="relative max-w-7xl mx-auto px-6 pt-32 pb-20 grid lg:grid-cols-2 gap-12 items-center">
                <!-- Text -->
                <div class="space-y-8">
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-blue-500/10 border border-blue-500/20 rounded-full text-blue-400 text-sm font-medium">
                        <span class="w-2 h-2 bg-blue-400 rounded-full animate-pulse"></span>
                        Finanzas familiares inteligentes
                    </div>

                    <h1 class="text-5xl lg:text-6xl font-extrabold leading-tight">
                        Tu familia,
                        <span class="block bg-gradient-to-r from-blue-400 via-cyan-400 to-blue-300 bg-clip-text text-transparent">
                            una sola vision
                        </span>
                        financiera
                    </h1>

                    <p class="text-lg text-gray-400 leading-relaxed max-w-lg">
                        Bynad Finance reune las finanzas de toda tu familia en un solo lugar. Gastos, ingresos, cuentas y patrimonio — organizados, claros y compartidos.
                    </p>

                    <div class="flex flex-wrap gap-4">
                        <Link :href="route('register')" class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-2xl font-semibold text-white shadow-2xl shadow-blue-500/30 hover:shadow-blue-500/50 hover:scale-105 transition-all duration-200">
                            Empezar gratis
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </Link>
                        <a href="/#features" class="inline-flex items-center gap-2 px-8 py-4 border border-white/10 rounded-2xl font-medium text-gray-300 hover:border-white/30 hover:text-white transition-all duration-200">
                            Ver funciones
                        </a>
                    </div>

                    <div class="flex items-center gap-6 pt-2">
                        <div class="flex -space-x-2">
                            <div v-for="c in ['#3b82f6','#8b5cf6','#10b981','#f59e0b']" :key="c" :style="{background: c}" class="w-9 h-9 rounded-full border-2 border-[#050d1f] flex items-center justify-center text-xs font-bold text-white">{{ c[1].toUpperCase() }}</div>
                        </div>
                        <p class="text-sm text-gray-400"><span class="text-white font-semibold">+500 familias</span> ya organizan sus finanzas</p>
                    </div>
                </div>

                <!-- Dashboard preview -->
                <div class="relative hidden lg:block">
                    <div class="absolute -inset-4 bg-gradient-to-r from-blue-500/20 to-cyan-500/20 rounded-3xl blur-2xl"></div>
                    <div class="relative bg-[#0d1929] border border-white/10 rounded-2xl p-6 shadow-2xl">
                        <!-- Fake header -->
                        <div class="flex items-center gap-2 mb-5">
                            <div class="w-3 h-3 rounded-full bg-rose-500"></div>
                            <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                            <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                            <div class="flex-1 mx-4 h-5 bg-white/5 rounded-full"></div>
                        </div>
                        <!-- Fake stat cards -->
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div v-for="(s, i) in [['Ingresos', '$485,000', 'emerald'],['Gastos', '$312,400', 'rose'],['Patrimonio', '$1.2M', 'blue'],['Ahorro', '$172,600', 'violet']]" :key="i"
                                class="bg-[#111d33] rounded-xl p-4 border border-white/5">
                                <p class="text-xs text-gray-500 mb-1">{{ s[0] }}</p>
                                <p class="font-bold text-white">{{ s[1] }}</p>
                                <div class="mt-2 h-1 rounded-full bg-white/5">
                                    <div :class="`bg-${s[2]}-500 h-1 rounded-full`" :style="`width: ${[72,48,90,36][i]}%`"></div>
                                </div>
                            </div>
                        </div>
                        <!-- Fake chart bars -->
                        <div class="bg-[#111d33] rounded-xl p-4 border border-white/5">
                            <p class="text-xs text-gray-500 mb-3">Evolucion mensual</p>
                            <div class="flex items-end gap-2 h-20">
                                <div v-for="(h, i) in [40,65,45,80,55,90,70,85,60,75,88,95]" :key="i"
                                    class="flex-1 bg-gradient-to-t from-blue-600 to-cyan-500 rounded-t-sm opacity-80 hover:opacity-100 transition-opacity"
                                    :style="`height: ${h}%`">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══ STATS ═══ -->
        <section id="stats-section" class="py-20 border-y border-white/5 bg-[#080f1e]">
            <div class="max-w-7xl mx-auto px-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                    <div class="space-y-2">
                        <p class="text-5xl font-extrabold bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent">+{{ counts.families }}</p>
                        <p class="text-gray-400">Familias organizadas</p>
                    </div>
                    <div class="space-y-2">
                        <p class="text-5xl font-extrabold bg-gradient-to-r from-violet-400 to-purple-400 bg-clip-text text-transparent">+{{ counts.expenses.toLocaleString() }}</p>
                        <p class="text-gray-400">Transacciones registradas</p>
                    </div>
                    <div class="space-y-2">
                        <p class="text-5xl font-extrabold bg-gradient-to-r from-emerald-400 to-teal-400 bg-clip-text text-transparent">{{ counts.savings }}%</p>
                        <p class="text-gray-400">De usuarios satisfechos</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══ FEATURES ═══ -->
        <section id="features" class="py-24">
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center mb-16 space-y-4">
                    <span class="inline-block px-4 py-1.5 bg-blue-500/10 border border-blue-500/20 rounded-full text-blue-400 text-sm font-medium">Funcionalidades</span>
                    <h2 class="text-4xl font-bold">Todo lo que tu familia necesita</h2>
                    <p class="text-gray-400 max-w-xl mx-auto">Cada herramienta diseñada para que la gestion financiera familiar sea simple, clara y efectiva.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="f in features" :key="f.title"
                        class="group relative bg-[#0d1929] border border-white/5 rounded-2xl p-6 hover:border-white/15 transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-500/5">
                        <div :class="`w-12 h-12 rounded-xl bg-gradient-to-br ${f.color} flex items-center justify-center mb-5 shadow-lg`">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="f.icon"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold mb-2 text-white">{{ f.title }}</h3>
                        <p class="text-gray-400 text-sm leading-relaxed">{{ f.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══ HOW IT WORKS ═══ -->
        <section class="py-24 bg-[#080f1e]">
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center mb-16 space-y-4">
                    <span class="inline-block px-4 py-1.5 bg-cyan-500/10 border border-cyan-500/20 rounded-full text-cyan-400 text-sm font-medium">Como funciona</span>
                    <h2 class="text-4xl font-bold">Tres pasos para empezar</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
                    <div class="hidden md:block absolute top-8 left-1/4 right-1/4 h-px bg-gradient-to-r from-transparent via-blue-500/30 to-transparent"></div>
                    <div v-for="s in steps" :key="s.n" class="text-center space-y-4">
                        <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-br from-blue-500/20 to-cyan-500/20 border border-blue-500/30 flex items-center justify-center">
                            <span class="text-2xl font-extrabold bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent">{{ s.n }}</span>
                        </div>
                        <h3 class="text-xl font-semibold">{{ s.title }}</h3>
                        <p class="text-gray-400 text-sm leading-relaxed">{{ s.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══ TRUST ═══ -->
        <section class="py-24">
            <div class="max-w-7xl mx-auto px-6">
                <div class="grid lg:grid-cols-2 gap-16 items-center">
                    <div class="space-y-6">
                        <span class="inline-block px-4 py-1.5 bg-emerald-500/10 border border-emerald-500/20 rounded-full text-emerald-400 text-sm font-medium">Privacidad y seguridad</span>
                        <h2 class="text-4xl font-bold">Tu informacion es tuya y solo tuya</h2>
                        <p class="text-gray-400 leading-relaxed">Bynad Finance corre en tu propio servidor. No compartimos, no vendemos, no accedemos a tu informacion financiera. Tus datos quedan donde vos decidis.</p>
                        <ul class="space-y-4">
                            <li v-for="item in ['Datos almacenados en tu servidor','Sin terceros con acceso','Backups bajo tu control','Codigo privado y seguro']" :key="item"
                                class="flex items-center gap-3 text-gray-300">
                                <div class="w-6 h-6 rounded-full bg-emerald-500/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                {{ item }}
                            </li>
                        </ul>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div v-for="(c, i) in [['🔒','Datos cifrados','Toda la comunicacion es encriptada'],['🏠','Self-hosted','Corre en tu propio servidor'],['👨‍👩‍👧','Multi-usuario','Un perfil por miembro familiar'],['📊','Reportes claros','Dashboards con datos en tiempo real']]" :key="i"
                            class="bg-[#0d1929] border border-white/5 rounded-2xl p-5 hover:border-white/10 transition-colors">
                            <p class="text-2xl mb-3">{{ c[0] }}</p>
                            <p class="font-semibold text-sm mb-1">{{ c[1] }}</p>
                            <p class="text-gray-500 text-xs">{{ c[2] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══ BLOG PREVIEW ═══ -->
        <section v-if="posts?.length" class="py-24 bg-[#080f1e]">
            <div class="max-w-7xl mx-auto px-6">
                <div class="flex items-center justify-between mb-12">
                    <div class="space-y-2">
                        <span class="inline-block px-4 py-1.5 bg-violet-500/10 border border-violet-500/20 rounded-full text-violet-400 text-sm font-medium">Blog</span>
                        <h2 class="text-3xl font-bold">Ultimas notas</h2>
                    </div>
                    <Link href="/blog" class="text-sm text-blue-400 hover:text-blue-300 flex items-center gap-1 transition-colors">
                        Ver todo
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </Link>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Link v-for="post in posts" :key="post.id" :href="`/blog/${post.slug}`"
                        class="group bg-[#0d1929] border border-white/5 rounded-2xl overflow-hidden hover:border-white/15 transition-all duration-300 hover:-translate-y-1">
                        <div class="h-48 bg-gradient-to-br from-blue-500/20 to-cyan-500/10 flex items-center justify-center">
                            <img v-if="post.cover_image" :src="post.cover_image" :alt="post.title" class="w-full h-full object-cover" />
                            <svg v-else class="w-12 h-12 text-blue-500/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div class="p-5">
                            <span class="text-xs text-blue-400 font-medium">{{ post.category }}</span>
                            <h3 class="font-semibold mt-1 mb-2 group-hover:text-blue-400 transition-colors">{{ post.title }}</h3>
                            <p v-if="post.excerpt" class="text-gray-500 text-sm line-clamp-2">{{ post.excerpt }}</p>
                        </div>
                    </Link>
                </div>
            </div>
        </section>

        <!-- ═══ CTA ═══ -->
        <section class="py-24">
            <div class="max-w-4xl mx-auto px-6 text-center">
                <div class="relative bg-gradient-to-br from-blue-600/20 to-cyan-600/10 border border-blue-500/20 rounded-3xl p-16 overflow-hidden">
                    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-96 h-px bg-gradient-to-r from-transparent via-blue-500 to-transparent"></div>
                    <div class="absolute -top-32 -right-32 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl"></div>
                    <div class="absolute -bottom-32 -left-32 w-64 h-64 bg-cyan-500/10 rounded-full blur-3xl"></div>
                    <div class="relative space-y-6">
                        <h2 class="text-4xl font-extrabold">Empeza hoy, gratis</h2>
                        <p class="text-gray-400 max-w-lg mx-auto">Registrate en minutos y tene toda la informacion financiera de tu familia organizada y al alcance de todos.</p>
                        <Link :href="route('register')" class="inline-flex items-center gap-2 px-10 py-4 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-2xl font-semibold text-white shadow-2xl shadow-blue-500/30 hover:shadow-blue-500/50 hover:scale-105 transition-all duration-200">
                            Crear cuenta gratuita
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </Link>
                    </div>
                </div>
            </div>
        </section>

    </LandingLayout>
</template>
