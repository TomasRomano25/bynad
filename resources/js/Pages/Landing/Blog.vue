<script setup>
import LandingLayout from '@/Layouts/LandingLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ posts: Object });
</script>

<template>
    <Head title="Blog - Bynad Finance" />
    <LandingLayout>
        <section class="pt-36 pb-24">
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center mb-16 space-y-4">
                    <span class="inline-block px-4 py-1.5 bg-violet-500/10 border border-violet-500/20 rounded-full text-violet-400 text-sm font-medium">Blog</span>
                    <h1 class="text-5xl font-extrabold">Notas y consejos</h1>
                    <p class="text-gray-400 max-w-xl mx-auto">Finanzas personales, ahorro familiar y todo lo que necesitas saber para gestionar mejor tu dinero.</p>
                </div>

                <div v-if="posts.data.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link v-for="post in posts.data" :key="post.id" :href="`/blog/${post.slug}`"
                        class="group bg-[#0d1929] border border-white/5 rounded-2xl overflow-hidden hover:border-white/15 transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-500/5">
                        <div class="h-52 bg-gradient-to-br from-blue-500/20 to-violet-500/10 flex items-center justify-center overflow-hidden">
                            <img v-if="post.cover_image" :src="post.cover_image" :alt="post.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                            <svg v-else class="w-14 h-14 text-blue-500/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-medium text-blue-400 bg-blue-500/10 px-3 py-1 rounded-full">{{ post.category }}</span>
                                <span class="text-xs text-gray-600">{{ new Date(post.published_at).toLocaleDateString('es-AR', { day: 'numeric', month: 'short', year: 'numeric' }) }}</span>
                            </div>
                            <h2 class="font-bold text-lg mb-2 group-hover:text-blue-400 transition-colors leading-snug">{{ post.title }}</h2>
                            <p v-if="post.excerpt" class="text-gray-500 text-sm line-clamp-3 leading-relaxed">{{ post.excerpt }}</p>
                            <div class="mt-4 flex items-center gap-2 text-blue-400 text-sm font-medium">
                                Leer mas
                                <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </div>
                    </Link>
                </div>

                <div v-else class="text-center py-24 space-y-4">
                    <svg class="w-16 h-16 text-gray-700 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p class="text-gray-500">Pronto publicaremos el primer articulo.</p>
                </div>

                <!-- Pagination -->
                <div v-if="posts.last_page > 1" class="flex justify-center gap-2 mt-12">
                    <Link v-for="link in posts.links" :key="link.label"
                        :href="link.url || '#'"
                        :class="['px-4 py-2 rounded-xl text-sm transition-colors', link.active ? 'bg-blue-500 text-white' : 'bg-[#0d1929] border border-white/5 text-gray-400 hover:border-white/20', !link.url ? 'opacity-30 pointer-events-none' : '']"
                        v-html="link.label" />
                </div>
            </div>
        </section>
    </LandingLayout>
</template>
