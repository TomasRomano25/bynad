<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ posts: Object });

const destroy = (post) => {
    if (confirm(`¿Eliminar "${post.title}"?`)) {
        router.delete(`/admin/posts/${post.id}`);
    }
};

const statusLabel = (post) => post.published_at && new Date(post.published_at) <= new Date() ? 'Publicado' : 'Borrador';
const statusClass = (post) => post.published_at && new Date(post.published_at) <= new Date()
    ? 'bg-green-500/10 text-green-400'
    : 'bg-yellow-500/10 text-yellow-400';
</script>

<template>
    <Head title="Entradas - Admin" />
    <AdminLayout>
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold">Entradas del blog</h1>
                <p class="text-gray-500 text-sm mt-1">{{ posts.total }} posts en total</p>
            </div>
            <Link href="/admin/posts/create"
                class="flex items-center gap-2 px-4 py-2 bg-blue-500 hover:bg-blue-600 rounded-xl text-sm font-medium transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nueva entrada
            </Link>
        </div>

        <div class="bg-[#0a1628] border border-white/5 rounded-2xl overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-white/5 text-xs text-gray-500 uppercase tracking-wider">
                        <th class="text-left px-6 py-4 font-medium">Título</th>
                        <th class="text-left px-6 py-4 font-medium">Categoría</th>
                        <th class="text-left px-6 py-4 font-medium">Autor</th>
                        <th class="text-left px-6 py-4 font-medium">Fecha</th>
                        <th class="text-left px-6 py-4 font-medium">Estado</th>
                        <th class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <tr v-for="post in posts.data" :key="post.id" class="hover:bg-white/2 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-medium text-sm">{{ post.title }}</p>
                            <p class="text-xs text-gray-600 mt-0.5">/blog/{{ post.slug }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-xs bg-blue-500/10 text-blue-400 px-2.5 py-1 rounded-full">{{ post.category }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-400">{{ post.user?.name ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ post.published_at ? new Date(post.published_at).toLocaleDateString('es-AR', { day: 'numeric', month: 'short', year: 'numeric' }) : '—' }}
                        </td>
                        <td class="px-6 py-4">
                            <span :class="['text-xs px-2.5 py-1 rounded-full font-medium', statusClass(post)]">
                                {{ statusLabel(post) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <Link :href="`/admin/posts/${post.id}/edit`"
                                    class="p-1.5 text-gray-500 hover:text-blue-400 hover:bg-blue-500/10 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </Link>
                                <Link :href="`/blog/${post.slug}`" target="_blank"
                                    class="p-1.5 text-gray-500 hover:text-gray-300 hover:bg-white/5 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </Link>
                                <button @click="destroy(post)"
                                    class="p-1.5 text-gray-500 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!posts.data.length">
                        <td colspan="6" class="px-6 py-16 text-center text-gray-600">
                            No hay posts todavía.
                            <Link href="/admin/posts/create" class="text-blue-400 hover:underline ml-1">Crear el primero</Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="posts.last_page > 1" class="flex justify-center gap-2 mt-6">
            <Link v-for="link in posts.links" :key="link.label"
                :href="link.url || '#'"
                :class="['px-3 py-1.5 rounded-lg text-sm transition-colors', link.active ? 'bg-blue-500 text-white' : 'bg-[#0a1628] border border-white/5 text-gray-400 hover:border-white/20', !link.url ? 'opacity-30 pointer-events-none' : '']"
                v-html="link.label" />
        </div>
    </AdminLayout>
</template>
