<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ items: Array });

const newItem = useForm({ label: '', url: '', target: '_self' });

const store = () => newItem.post('/admin/menu', { onSuccess: () => newItem.reset() });

const editing = ref(null);
const editForm = useForm({ label: '', url: '', target: '_self', is_active: true });

const startEdit = (item) => {
    editing.value = item.id;
    editForm.label = item.label;
    editForm.url = item.url;
    editForm.target = item.target;
    editForm.is_active = item.is_active;
};

const saveEdit = (item) => {
    editForm.put(`/admin/menu/${item.id}`, { onSuccess: () => { editing.value = null; } });
};

const destroy = (item) => {
    if (confirm(`¿Eliminar "${item.label}" del menú?`)) {
        router.delete(`/admin/menu/${item.id}`);
    }
};

const move = (index, direction) => {
    const newItems = [...props.items];
    const swapIndex = index + direction;
    if (swapIndex < 0 || swapIndex >= newItems.length) return;
    [newItems[index], newItems[swapIndex]] = [newItems[swapIndex], newItems[index]];
    router.put('/admin/menu/reorder', { items: newItems.map(i => i.id) }, { preserveScroll: true });
};
</script>

<template>
    <Head title="Menú - Admin" />
    <AdminLayout>
        <div class="max-w-2xl">
            <div class="mb-8">
                <h1 class="text-2xl font-bold">Menú del sitio</h1>
                <p class="text-gray-500 text-sm mt-1">Gestioná los links que aparecen en la barra de navegación de la landing.</p>
            </div>

            <!-- Items list -->
            <div class="bg-[#0a1628] border border-white/5 rounded-2xl overflow-hidden mb-6">
                <div v-if="!items.length" class="px-6 py-12 text-center text-gray-600">
                    No hay items en el menú. Agregá uno abajo.
                </div>

                <div v-for="(item, index) in items" :key="item.id" class="border-b border-white/5 last:border-0">
                    <!-- View mode -->
                    <div v-if="editing !== item.id" class="flex items-center gap-4 px-5 py-4">
                        <!-- Reorder buttons -->
                        <div class="flex flex-col gap-0.5">
                            <button @click="move(index, -1)" :disabled="index === 0"
                                class="p-1 text-gray-600 hover:text-gray-300 disabled:opacity-20 disabled:cursor-not-allowed transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                            </button>
                            <button @click="move(index, 1)" :disabled="index === items.length - 1"
                                class="p-1 text-gray-600 hover:text-gray-300 disabled:opacity-20 disabled:cursor-not-allowed transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-sm">{{ item.label }}</span>
                                <span v-if="!item.is_active" class="text-xs bg-gray-500/10 text-gray-500 px-2 py-0.5 rounded-full">Oculto</span>
                                <span v-if="item.target === '_blank'" class="text-xs bg-violet-500/10 text-violet-400 px-2 py-0.5 rounded-full">Nueva pestaña</span>
                            </div>
                            <p class="text-xs text-gray-600 mt-0.5">{{ item.url }}</p>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <button @click="startEdit(item)"
                                class="p-1.5 text-gray-500 hover:text-blue-400 hover:bg-blue-500/10 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button @click="destroy(item)"
                                class="p-1.5 text-gray-500 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Edit mode -->
                    <div v-else class="p-5 bg-blue-500/5 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1.5">Nombre</label>
                                <input v-model="editForm.label" type="text" class="w-full bg-[#060e20] border border-white/10 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" />
                                <p v-if="editForm.errors.label" class="text-red-400 text-xs mt-1">{{ editForm.errors.label }}</p>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1.5">URL</label>
                                <input v-model="editForm.url" type="text" class="w-full bg-[#060e20] border border-white/10 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" />
                                <p v-if="editForm.errors.url" class="text-red-400 text-xs mt-1">{{ editForm.errors.url }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="editForm.is_active" type="checkbox" class="rounded" />
                                <span class="text-sm text-gray-400">Visible</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" :checked="editForm.target === '_blank'" @change="editForm.target = $event.target.checked ? '_blank' : '_self'" class="rounded" />
                                <span class="text-sm text-gray-400">Abrir en nueva pestaña</span>
                            </label>
                        </div>
                        <div class="flex gap-2">
                            <button @click="saveEdit(item)" :disabled="editForm.processing"
                                class="px-4 py-1.5 bg-blue-500 hover:bg-blue-600 rounded-lg text-sm font-medium transition-colors disabled:opacity-50">
                                Guardar
                            </button>
                            <button @click="editing = null" class="px-4 py-1.5 bg-white/5 hover:bg-white/10 rounded-lg text-sm transition-colors">
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add new item -->
            <div class="bg-[#0a1628] border border-white/5 rounded-2xl p-5">
                <h2 class="text-sm font-semibold mb-4">Agregar ítem</h2>
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1.5">Nombre</label>
                        <input v-model="newItem.label" type="text" placeholder="Ej: Precios" class="w-full bg-[#060e20] border border-white/10 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" />
                        <p v-if="newItem.errors.label" class="text-red-400 text-xs mt-1">{{ newItem.errors.label }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1.5">URL</label>
                        <input v-model="newItem.url" type="text" placeholder="Ej: /precios o https://..." class="w-full bg-[#060e20] border border-white/10 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" />
                        <p v-if="newItem.errors.url" class="text-red-400 text-xs mt-1">{{ newItem.errors.url }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" :checked="newItem.target === '_blank'" @change="newItem.target = $event.target.checked ? '_blank' : '_self'" class="rounded" />
                        <span class="text-sm text-gray-400">Abrir en nueva pestaña</span>
                    </label>
                    <button @click="store" :disabled="newItem.processing"
                        class="flex items-center gap-2 px-4 py-2 bg-blue-500 hover:bg-blue-600 rounded-xl text-sm font-medium transition-colors disabled:opacity-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Agregar
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
