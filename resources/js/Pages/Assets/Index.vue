<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/UI/Modal.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { formatMoney, assetTypes } from '@/helpers';

const props = defineProps({ assets: Array, accounts: Array, totalArs: Number, totalUsd: Number, byType: Array, familyUsers: Array, usdRate: Number });
const showModal = ref(false);
const editing = ref(null);
const form = useForm({ user_id: '', name: '', type: 'inmueble', value_ars: 0, value_usd: 0, currency_input: 'ARS', description: '' });

const openCreate = () => { editing.value = null; form.reset(); form.user_id = props.familyUsers?.[0]?.id ?? ''; showModal.value = true; };
const openEdit = (a) => {
    editing.value = a;
    Object.assign(form, { user_id: a.user_id, name: a.name, type: a.type, value_ars: a.value_ars, value_usd: a.value_usd, currency_input: a.currency_input, description: a.description });
    showModal.value = true;
};
const submit = () => {
    if (editing.value) form.put(route('assets.update', editing.value.id), { onSuccess: () => showModal.value = false });
    else form.post(route('assets.store'), { onSuccess: () => { showModal.value = false; form.reset(); } });
};
const destroy = (a) => { if (confirm('Eliminar?')) useForm({}).delete(route('assets.destroy', a.id)); };

// Selection calculator
const selectedAssets = ref(new Set());
const selectedAccounts = ref(new Set());

const toggleAsset = (id) => {
    const s = new Set(selectedAssets.value);
    s.has(id) ? s.delete(id) : s.add(id);
    selectedAssets.value = s;
};
const toggleAccount = (id) => {
    const s = new Set(selectedAccounts.value);
    s.has(id) ? s.delete(id) : s.add(id);
    selectedAccounts.value = s;
};
const clearSelection = () => { selectedAssets.value = new Set(); selectedAccounts.value = new Set(); };

const selectionCount = computed(() => selectedAssets.value.size + selectedAccounts.value.size);

const selectionTotalUsd = computed(() => {
    let total = 0;
    for (const id of selectedAssets.value) {
        const a = props.assets.find(a => a.id === id);
        if (a) total += parseFloat(a.value_usd ?? 0);
    }
    for (const id of selectedAccounts.value) {
        const a = props.accounts.find(a => a.id === id);
        if (a) total += a.currency === 'USD' ? parseFloat(a.balance) : parseFloat(a.balance) / props.usdRate;
    }
    return total;
});

const selectionTotalArs = computed(() => selectionTotalUsd.value * props.usdRate);

const typeIcons = {
    inmueble: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5',
    vehiculo: 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z',
    inversion: 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
    crypto: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1',
    ahorro: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
    otro: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
};
const typeColors = { inmueble: '#6366f1', vehiculo: '#f59e0b', inversion: '#10b981', crypto: '#8b5cf6', ahorro: '#06b6d4', otro: '#6b7280' };
</script>

<template>
    <Head title="Patrimonio" />
    <AuthenticatedLayout>
        <div class="space-y-6 pb-28">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Patrimonio</h1>
                    <p class="text-sm text-gray-500 mt-1">Tus activos y su valor actual</p>
                </div>
                <button @click="openCreate" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-purple-500 to-indigo-600 text-white text-sm font-medium rounded-xl shadow-lg shadow-purple-200/50 hover:shadow-xl transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Nuevo Activo
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-gradient-to-r from-purple-500 to-indigo-600 rounded-2xl p-6 text-white shadow-xl">
                    <p class="text-purple-200 text-sm">Patrimonio total (ARS)</p>
                    <p class="text-3xl font-bold mt-1">{{ formatMoney(totalArs) }}</p>
                </div>
                <div class="bg-gradient-to-r from-emerald-500 to-cyan-600 rounded-2xl p-6 text-white shadow-xl">
                    <p class="text-emerald-200 text-sm">Patrimonio total (USD)</p>
                    <p class="text-3xl font-bold mt-1">{{ formatMoney(totalUsd, 'USD') }}</p>
                </div>
            </div>

            <!-- By type -->
            <div v-if="byType.length" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div v-for="t in byType" :key="t.type" class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
                    <div :style="{ backgroundColor: typeColors[t.type] + '15' }" class="w-10 h-10 rounded-xl mx-auto flex items-center justify-center mb-2">
                        <svg :style="{ color: typeColors[t.type] }" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" :d="typeIcons[t.type]" /></svg>
                    </div>
                    <p class="text-xs text-gray-500">{{ assetTypes[t.type] }}</p>
                    <p class="text-sm font-bold text-gray-800 mt-1">{{ formatMoney(t.total_usd, 'USD') }}</p>
                    <p class="text-xs text-gray-400">{{ t.count }} activo{{ t.count > 1 ? 's' : '' }}</p>
                </div>
            </div>

            <!-- Hint -->
            <div class="flex items-center gap-2 text-xs text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                Toca cualquier activo o cuenta para seleccionarlo y calcular el total
            </div>

            <!-- Cuentas bancarias y billeteras -->
            <div v-if="accounts?.length" class="space-y-3">
                <h2 class="text-base font-semibold text-gray-700">Cuentas bancarias y billeteras</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="account in accounts" :key="account.id"
                        @click="toggleAccount(account.id)"
                        :class="selectedAccounts.has(account.id) ? 'ring-2 ring-indigo-500 bg-indigo-50/50' : 'bg-white hover:shadow-md'"
                        class="rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-4 cursor-pointer transition-all select-none">
                        <div class="relative shrink-0">
                            <div :style="{ backgroundColor: (account.color || '#6366f1') + '20' }" class="w-10 h-10 rounded-xl flex items-center justify-center">
                                <svg v-if="!selectedAccounts.has(account.id)" :style="{ color: account.color || '#6366f1' }" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                <svg v-else class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800 truncate">{{ account.name }}</p>
                            <p class="text-xs text-gray-400">{{ account.user?.name }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm font-bold text-gray-800">{{ formatMoney(account.balance, account.currency) }}</p>
                            <p class="text-xs text-gray-400">
                                <template v-if="account.currency === 'USD'">≈ {{ formatMoney(account.balance * usdRate) }}</template>
                                <template v-else>≈ {{ formatMoney(account.balance_usd, 'USD') }}</template>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assets grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="asset in assets" :key="asset.id"
                    :class="selectedAssets.has(asset.id) ? 'ring-2 ring-indigo-500 bg-indigo-50/50' : 'bg-white hover:shadow-md'"
                    class="rounded-2xl p-5 shadow-sm border border-gray-100 transition-all">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3 flex-1 min-w-0 cursor-pointer select-none" @click="toggleAsset(asset.id)">
                            <div :style="{ backgroundColor: typeColors[asset.type] + '15' }" class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0">
                                <svg v-if="!selectedAssets.has(asset.id)" :style="{ color: typeColors[asset.type] }" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" :d="typeIcons[asset.type]" /></svg>
                                <svg v-else class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-semibold text-gray-800 truncate">{{ asset.name }}</h3>
                                <p class="text-xs text-gray-400">{{ assetTypes[asset.type] }} - {{ asset.user?.name }}</p>
                            </div>
                        </div>
                        <div class="flex gap-1 shrink-0 ml-2">
                            <button @click.stop="openEdit(asset)" class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
                            <button @click.stop="destroy(asset)" class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-gray-50 rounded-lg p-2 text-center"><p class="text-xs text-gray-500">ARS</p><p class="text-sm font-bold text-gray-800">{{ formatMoney(asset.value_ars) }}</p></div>
                        <div class="bg-gray-50 rounded-lg p-2 text-center"><p class="text-xs text-gray-500">USD</p><p class="text-sm font-bold text-gray-800">{{ formatMoney(asset.value_usd, 'USD') }}</p></div>
                    </div>
                    <p v-if="asset.description" class="text-xs text-gray-400 mt-3">{{ asset.description }}</p>
                </div>
            </div>
            <div v-if="!assets.length" class="bg-white rounded-2xl p-12 text-center shadow-sm border border-gray-100"><p class="text-gray-400">Sin activos cargados</p></div>
        </div>

        <!-- Floating calculator bar -->
        <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="translate-y-full opacity-0" enter-to-class="translate-y-0 opacity-100" leave-active-class="transition-all duration-200 ease-in" leave-from-class="translate-y-0 opacity-100" leave-to-class="translate-y-full opacity-0">
            <div v-if="selectionCount > 0" class="fixed bottom-0 left-0 right-0 z-50 p-4">
                <div class="max-w-4xl mx-auto bg-gray-900 rounded-2xl shadow-2xl px-5 py-4 flex items-center gap-4 flex-wrap">
                    <div class="flex items-center gap-2 shrink-0">
                        <div class="w-8 h-8 rounded-lg bg-indigo-500 flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        </div>
                        <span class="text-white/70 text-sm">{{ selectionCount }} seleccionado{{ selectionCount > 1 ? 's' : '' }}</span>
                    </div>
                    <div class="flex-1 flex items-center gap-6 flex-wrap">
                        <div>
                            <p class="text-white/50 text-xs uppercase tracking-wider">Total ARS</p>
                            <p class="text-white font-bold text-lg leading-tight">{{ formatMoney(selectionTotalArs) }}</p>
                        </div>
                        <div>
                            <p class="text-white/50 text-xs uppercase tracking-wider">Total USD</p>
                            <p class="text-emerald-400 font-bold text-lg leading-tight">{{ formatMoney(selectionTotalUsd, 'USD') }}</p>
                        </div>
                    </div>
                    <button @click="clearSelection" class="shrink-0 px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white/70 hover:text-white text-sm rounded-lg transition-colors">
                        Limpiar
                    </button>
                </div>
            </div>
        </Transition>

        <Modal :show="showModal" @close="showModal = false" :title="editing ? 'Editar Activo' : 'Nuevo Activo'">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Titular <span class="text-red-500">*</span></label>
                    <select v-model="form.user_id" required class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="" disabled>Selecciona un miembro</option>
                        <option v-for="u in familyUsers" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                    <p v-if="form.errors.user_id" class="text-rose-500 text-xs mt-1">{{ form.errors.user_id }}</p>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label><input v-model="form.name" type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="Ej: Departamento en Tandil" /></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label><select v-model="form.type" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500"><option v-for="(l,k) in assetTypes" :key="k" :value="k">{{ l }}</option></select></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Moneda del valor</label><select v-model="form.currency_input" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500"><option value="ARS">ARS (pesos)</option><option value="USD">USD (dolares)</option></select></div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor en {{ form.currency_input }}</label>
                    <input v-if="form.currency_input === 'ARS'" v-model="form.value_ars" type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" />
                    <input v-else v-model="form.value_usd" type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" />
                    <p class="text-xs text-gray-400 mt-1">Se convertira automaticamente a {{ form.currency_input === 'ARS' ? 'USD' : 'ARS' }} con cotizacion {{ formatMoney(usdRate) }}/USD</p>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Descripcion</label><textarea v-model="form.description" rows="2" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500"></textarea></div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="showModal = false" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200">Cancelar</button>
                    <button type="submit" :disabled="form.processing" class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-purple-500 to-indigo-600 rounded-xl shadow-lg disabled:opacity-50">{{ editing ? 'Guardar' : 'Crear' }}</button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
