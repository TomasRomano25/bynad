<script setup>
import LandingLayout from '@/Layouts/LandingLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ post: Object });
</script>

<template>
    <Head :title="`${post.title} - Bynad Finance`" />
    <LandingLayout>
        <section class="pt-36 pb-24">
            <div class="max-w-4xl mx-auto px-6">

                <!-- Back -->
                <Link href="/blog" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-300 transition-colors mb-10 group">
                    <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Volver al blog
                </Link>

                <!-- Header -->
                <div class="mb-10">
                    <div class="flex items-center gap-3 mb-5">
                        <span class="text-xs font-medium text-blue-400 bg-blue-500/10 px-3 py-1 rounded-full">{{ post.category }}</span>
                        <span class="text-xs text-gray-600">{{ new Date(post.published_at).toLocaleDateString('es-AR', { day: 'numeric', month: 'long', year: 'numeric' }) }}</span>
                    </div>
                    <h1 class="text-4xl md:text-5xl font-extrabold leading-tight mb-6">{{ post.title }}</h1>
                    <p v-if="post.excerpt" class="text-xl text-gray-400 leading-relaxed">{{ post.excerpt }}</p>
                </div>

                <!-- Cover image -->
                <div v-if="post.cover_image" class="rounded-2xl overflow-hidden mb-12 aspect-video">
                    <img :src="post.cover_image" :alt="post.title" class="w-full h-full object-cover" />
                </div>
                <div v-else class="rounded-2xl overflow-hidden mb-12 h-64 bg-gradient-to-br from-blue-500/20 to-violet-500/10 flex items-center justify-center">
                    <svg class="w-20 h-20 text-blue-500/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>

                <!-- Content -->
                <article class="prose prose-invert prose-lg max-w-none
                    prose-headings:font-bold prose-headings:text-white
                    prose-h2:text-3xl prose-h2:mt-12 prose-h2:mb-4
                    prose-h3:text-xl prose-h3:mt-8 prose-h3:mb-3
                    prose-p:text-gray-400 prose-p:leading-relaxed prose-p:mb-6
                    prose-strong:text-white
                    prose-a:text-blue-400 prose-a:no-underline hover:prose-a:underline
                    prose-ul:text-gray-400 prose-ol:text-gray-400
                    prose-li:mb-2
                    prose-blockquote:border-l-blue-500 prose-blockquote:text-gray-400 prose-blockquote:bg-blue-500/5 prose-blockquote:rounded-r-xl prose-blockquote:py-1
                    prose-code:text-blue-300 prose-code:bg-blue-500/10 prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded prose-code:text-sm prose-code:before:content-none prose-code:after:content-none
                    prose-pre:bg-[#0d1929] prose-pre:border prose-pre:border-white/5 prose-pre:rounded-xl
                    prose-img:rounded-xl prose-img:border prose-img:border-white/5
                    prose-hr:border-white/10"
                    v-html="post.content" />

                <!-- Author -->
                <div v-if="post.user" class="mt-16 pt-8 border-t border-white/10 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                        {{ post.user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Escrito por</p>
                        <p class="font-semibold">{{ post.user.name }}</p>
                    </div>
                </div>

                <!-- CTA -->
                <div class="mt-16 p-8 rounded-2xl bg-gradient-to-r from-blue-500/10 to-cyan-500/10 border border-blue-500/20 text-center space-y-4">
                    <h3 class="text-2xl font-bold">Empeza a gestionar tus finanzas hoy</h3>
                    <p class="text-gray-400">Registrate gratis y toma el control de tu economia familiar.</p>
                    <Link :href="route('register')" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-xl font-semibold text-white hover:shadow-lg hover:shadow-blue-500/25 transition-all">
                        Crear cuenta gratis
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </Link>
                </div>
            </div>
        </section>
    </LandingLayout>
</template>
