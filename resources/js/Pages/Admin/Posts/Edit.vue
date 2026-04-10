<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({ post: Object });

const toInputDate = (d) => d ? new Date(d).toISOString().slice(0, 16) : '';

const form = useForm({
    title:        props.post?.title        ?? '',
    slug:         props.post?.slug         ?? '',
    category:     props.post?.category     ?? '',
    excerpt:      props.post?.excerpt      ?? '',
    content:      props.post?.content      ?? '',
    cover_image:  props.post?.cover_image  ?? '',
    published_at: toInputDate(props.post?.published_at),
});

const isEdit = !!props.post;

const slugify = (str) => str
    .toLowerCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
    .trim();

// Auto-generate slug only for new posts
if (!isEdit) {
    watch(() => form.title, (val) => { form.slug = slugify(val); });
}

const save = (publish = false) => {
    if (publish && !form.published_at) {
        form.published_at = new Date().toISOString().slice(0, 16);
    }
    if (isEdit) {
        form.put(`/admin/posts/${props.post.id}`);
    } else {
        form.post('/admin/posts');
    }
};

const categories = ['Finanzas', 'Ahorro', 'Inversiones', 'Gastos', 'Presupuesto', 'Familia', 'Consejos'];
</script>

<template>
    <Head :title="isEdit ? `Editar: ${post.title}` : 'Nueva entrada'" />
    <AdminLayout>
        <div class="max-w-5xl">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold">{{ isEdit ? 'Editar entrada' : 'Nueva entrada' }}</h1>
                    <p v-if="isEdit" class="text-gray-500 text-sm mt-1">/blog/{{ form.slug }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="save(false)" :disabled="form.processing"
                        class="px-4 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-sm font-medium transition-colors disabled:opacity-50">
                        Guardar borrador
                    </button>
                    <button @click="save(true)" :disabled="form.processing"
                        class="px-4 py-2 bg-blue-500 hover:bg-blue-600 rounded-xl text-sm font-medium transition-colors disabled:opacity-50">
                        {{ form.published_at ? 'Guardar y publicar' : 'Publicar ahora' }}
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-6">
                <!-- Main content -->
                <div class="col-span-2 space-y-5">
                    <!-- Title -->
                    <div>
                        <input v-model="form.title" type="text" placeholder="Título del post..."
                            class="w-full bg-[#0a1628] border border-white/5 rounded-2xl px-5 py-4 text-xl font-bold placeholder-gray-700 focus:outline-none focus:border-blue-500/50 transition-colors" />
                        <p v-if="form.errors.title" class="text-red-400 text-xs mt-2">{{ form.errors.title }}</p>
                    </div>

                    <!-- Slug -->
                    <div>
                        <label class="block text-xs text-gray-500 mb-1.5">Slug (URL)</label>
                        <div class="flex items-center bg-[#0a1628] border border-white/5 rounded-xl overflow-hidden focus-within:border-blue-500/50 transition-colors">
                            <span class="px-3 py-2.5 text-sm text-gray-600 border-r border-white/5 bg-white/2">/blog/</span>
                            <input v-model="form.slug" type="text" class="flex-1 px-3 py-2.5 text-sm bg-transparent focus:outline-none" />
                        </div>
                        <p v-if="form.errors.slug" class="text-red-400 text-xs mt-1">{{ form.errors.slug }}</p>
                    </div>

                    <!-- Excerpt -->
                    <div>
                        <label class="block text-xs text-gray-500 mb-1.5">Resumen <span class="text-gray-700">(opcional — aparece en la lista del blog)</span></label>
                        <textarea v-model="form.excerpt" rows="2" placeholder="Breve descripción del artículo..."
                            class="w-full bg-[#0a1628] border border-white/5 rounded-xl px-4 py-3 text-sm placeholder-gray-700 focus:outline-none focus:border-blue-500/50 transition-colors resize-none" />
                        <p v-if="form.errors.excerpt" class="text-red-400 text-xs mt-1">{{ form.errors.excerpt }}</p>
                    </div>

                    <!-- Content -->
                    <div>
                        <label class="block text-xs text-gray-500 mb-1.5">
                            Contenido
                            <span class="text-gray-700 ml-1">— acepta HTML (p, h2, h3, ul, ol, strong, a, blockquote, code, pre)</span>
                        </label>
                        <textarea v-model="form.content" rows="22" placeholder="Escribí el contenido del post en HTML..."
                            class="w-full bg-[#0a1628] border border-white/5 rounded-xl px-4 py-3 text-sm font-mono placeholder-gray-700 focus:outline-none focus:border-blue-500/50 transition-colors resize-y" />
                        <p v-if="form.errors.content" class="text-red-400 text-xs mt-1">{{ form.errors.content }}</p>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-5">
                    <!-- Publish -->
                    <div class="bg-[#0a1628] border border-white/5 rounded-2xl p-5 space-y-4">
                        <h2 class="text-sm font-semibold">Publicación</h2>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1.5">Fecha de publicación</label>
                            <input v-model="form.published_at" type="datetime-local"
                                class="w-full bg-[#060e20] border border-white/10 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 [color-scheme:dark]" />
                            <p class="text-xs text-gray-700 mt-1.5">Dejá vacío para guardar como borrador.</p>
                        </div>
                    </div>

                    <!-- Category -->
                    <div class="bg-[#0a1628] border border-white/5 rounded-2xl p-5 space-y-3">
                        <h2 class="text-sm font-semibold">Categoría</h2>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="cat in categories" :key="cat" @click="form.category = cat"
                                :class="['px-3 py-1.5 rounded-lg text-xs font-medium transition-colors', form.category === cat ? 'bg-blue-500 text-white' : 'bg-white/5 text-gray-400 hover:bg-white/10']">
                                {{ cat }}
                            </button>
                        </div>
                        <input v-model="form.category" type="text" placeholder="O escribí una..."
                            class="w-full bg-[#060e20] border border-white/10 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 mt-1" />
                        <p v-if="form.errors.category" class="text-red-400 text-xs">{{ form.errors.category }}</p>
                    </div>

                    <!-- Cover image -->
                    <div class="bg-[#0a1628] border border-white/5 rounded-2xl p-5 space-y-3">
                        <h2 class="text-sm font-semibold">Imagen de portada</h2>
                        <input v-model="form.cover_image" type="text" placeholder="https://... o /storage/..."
                            class="w-full bg-[#060e20] border border-white/10 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" />
                        <div v-if="form.cover_image" class="rounded-lg overflow-hidden aspect-video bg-white/5">
                            <img :src="form.cover_image" class="w-full h-full object-cover" @error="$event.target.style.display='none'" />
                        </div>
                        <p v-if="form.errors.cover_image" class="text-red-400 text-xs">{{ form.errors.cover_image }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
