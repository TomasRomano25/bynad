<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const props = defineProps({ values: Object });
const backups = ref([]);

const form = ref({ ...props.values });

onMounted(() => {
    loadBackups();
});

const saving = ref(false);
const save = () => {
    saving.value = true;
    router.put(route('admin.settings.update'), { values: form.value }, {
        onFinish: () => { saving.value = false; },
    });
};

const createBackup = () => { router.post(route('admin.settings.backup')); };

const loadBackups = async () => {
    try {
        const res = await fetch(route('admin.settings.backups'));
        backups.value = await res.json();
    } catch (e) {}
};
</script>

<template>
    <Head title="Configuracion" />
    <AdminLayout>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Configuracion</h1>
                <p class="text-sm text-gray-500 mt-1">Ajusta la configuracion de la plataforma</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Cotizacion USD -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Cotizacion USD
                    </h3>
                    <p class="text-sm text-gray-500 mb-3">Este valor se usa para convertir entre pesos y dolares en toda la plataforma.</p>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-600">1 USD =</span>
                        <input v-model="form.usd_rate" type="number" step="0.01" class="w-40 border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        <span class="text-sm text-gray-600">ARS</span>
                    </div>
                </div>

                <!-- SMTP -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        Configuracion SMTP
                    </h3>
                    <div class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="block text-xs text-gray-500 mb-1">Host</label><input v-model="form.smtp_host" type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" /></div>
                            <div><label class="block text-xs text-gray-500 mb-1">Puerto</label><input v-model="form.smtp_port" type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" /></div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="block text-xs text-gray-500 mb-1">Usuario</label><input v-model="form.smtp_user" type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" /></div>
                            <div><label class="block text-xs text-gray-500 mb-1">Password</label><input v-model="form.smtp_password" type="password" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" /></div>
                        </div>
                        <div><label class="block text-xs text-gray-500 mb-1">Email remitente</label><input v-model="form.smtp_from" type="email" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" /></div>
                    </div>
                </div>

                <!-- Backups -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 lg:col-span-2">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-semibold text-gray-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
                            Backups de Base de Datos
                        </h3>
                        <button @click="createBackup" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-100 text-amber-700 text-sm font-medium rounded-xl hover:bg-amber-200 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            Crear Backup
                        </button>
                    </div>
                    <div class="space-y-3">
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Frecuencia</label>
                                <select v-model="form.backup_frequency" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    <option value="daily">Diario</option>
                                    <option value="weekly">Semanal</option>
                                    <option value="monthly">Mensual</option>
                                </select>
                            </div>
                            <div><label class="block text-xs text-gray-500 mb-1">Ruta de backups</label><input v-model="form.backup_path" type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" /></div>
                        </div>
                        <div v-if="backups.length" class="space-y-2">
                            <div v-for="b in backups" :key="b.name" class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">{{ b.name }}</p>
                                        <p class="text-xs text-gray-400">{{ b.size }} | {{ b.date }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-400 text-center py-4">No hay backups creados</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button @click="save" :disabled="saving" class="px-8 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-medium rounded-xl shadow-lg shadow-indigo-200/50 hover:shadow-xl transition-all disabled:opacity-50">
                    {{ saving ? 'Guardando...' : 'Guardar Configuracion' }}
                </button>
            </div>
        </div>
    </AdminLayout>
</template>
