<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/UI/Modal.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { formatMoney, accountTypes } from '@/helpers';

const props = defineProps({ accounts: Array, familyUsers: Array, usdRate: Number });

const showModal = ref(false);
const showTransferModal = ref(false);
const editing = ref(null);

const transferForm = useForm({ from_account_id: '', to_account_id: '', amount: 0, commission: 0, notes: '' });

const fromAccount = computed(() => props.accounts.find(a => a.id == transferForm.from_account_id));
const toAccount   = computed(() => props.accounts.find(a => a.id == transferForm.to_account_id));

const convertedAmount = computed(() => {
    if (!fromAccount.value || !toAccount.value) return null;
    const a = parseFloat(transferForm.amount) || 0;
    if (fromAccount.value.currency === toAccount.value.currency) return null;
    if (fromAccount.value.currency === 'USD') return formatMoney(a * props.usdRate);
    return formatMoney(a / props.usdRate, 'USD');
});

const submitTransfer = () => {
    transferForm.post(route('accounts.transfer'), {
        onSuccess: () => { showTransferModal.value = false; transferForm.reset(); },
    });
};

const form = useForm({
    user_id: '', name: '', type: 'banco', institution: '', currency: 'ARS', balance: 0, color: '#6366f1',
});

const openCreate = () => { editing.value = null; form.reset(); form.user_id = props.familyUsers?.[0]?.id ?? ''; showModal.value = true; };
const openEdit = (account) => {
    editing.value = account;
    form.user_id = account.user_id; form.name = account.name; form.type = account.type; form.institution = account.institution;
    form.currency = account.currency; form.balance = account.balance; form.color = account.color;
    showModal.value = true;
};

const submit = () => {
    if (editing.value) {
        form.put(route('accounts.update', editing.value.id), { onSuccess: () => { showModal.value = false; } });
    } else {
        form.post(route('accounts.store'), { onSuccess: () => { showModal.value = false; form.reset(); } });
    }
};

const destroy = (account) => {
    if (confirm('Eliminar esta cuenta?')) {
        useForm({}).delete(route('accounts.destroy', account.id));
    }
};

const typeIcons = {
    banco: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
    billetera_virtual: 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
    efectivo: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
    otro: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
};
</script>

<template>
    <Head title="Cuentas" />
    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Cuentas</h1>
                    <p class="text-sm text-gray-500 mt-1">Administra tus cuentas bancarias y billeteras</p>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="showTransferModal = true" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white text-indigo-600 text-sm font-medium rounded-xl border border-indigo-200 shadow-sm hover:bg-indigo-50 transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                        Transferir
                    </button>
                    <button @click="openCreate" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-sm font-medium rounded-xl shadow-lg shadow-indigo-200/50 hover:shadow-xl hover:shadow-indigo-300/50 transition-all duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Nueva Cuenta
                    </button>
                </div>
            </div>

            <div v-if="!accounts.length" class="bg-white rounded-2xl p-12 text-center shadow-sm border border-gray-100">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                <p class="text-gray-500">No tenes cuentas cargadas</p>
                <button @click="openCreate" class="mt-4 text-indigo-600 text-sm font-medium hover:text-indigo-700">Agregar primera cuenta</button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="account in accounts" :key="account.id"
                    class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div :style="{ backgroundColor: account.color + '20' }" class="w-11 h-11 rounded-xl flex items-center justify-center">
                                <svg :style="{ color: account.color }" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="typeIcons[account.type] || typeIcons.otro" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-800">{{ account.name }}</h3>
                                <p class="text-xs text-gray-400">{{ accountTypes[account.type] }} {{ account.institution ? '- ' + account.institution : '' }}</p>
                            </div>
                        </div>
                        <div class="flex gap-1">
                            <button @click="openEdit(account)" class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            </button>
                            <button @click="destroy(account)" class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-end justify-between">
                        <div>
                            <p class="text-2xl font-bold text-gray-800">{{ formatMoney(account.balance, account.currency) }}</p>
                            <p class="text-xs text-gray-400">
                                <template v-if="account.currency === 'USD'">≈ {{ formatMoney(account.balance * usdRate) }} ARS</template>
                                <template v-else>≈ {{ formatMoney(account.balance_usd, 'USD') }}</template>
                            </p>
                        </div>
                        <span class="text-xs text-gray-400">{{ account.user?.name }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transfer Modal -->
        <Modal :show="showTransferModal" @close="showTransferModal = false" title="Transferir entre cuentas">
            <form @submit.prevent="submitTransfer" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Desde <span class="text-red-500">*</span></label>
                        <select v-model="transferForm.from_account_id" required class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="" disabled>Cuenta origen</option>
                            <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }} ({{ formatMoney(a.balance, a.currency) }})</option>
                        </select>
                        <p v-if="transferForm.errors.from_account_id" class="text-rose-500 text-xs mt-1">{{ transferForm.errors.from_account_id }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hacia <span class="text-red-500">*</span></label>
                        <select v-model="transferForm.to_account_id" required class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="" disabled>Cuenta destino</option>
                            <option v-for="a in accounts" :key="a.id" :value="a.id" :disabled="a.id == transferForm.from_account_id">{{ a.name }} ({{ formatMoney(a.balance, a.currency) }})</option>
                        </select>
                        <p v-if="transferForm.errors.to_account_id" class="text-rose-500 text-xs mt-1">{{ transferForm.errors.to_account_id }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Monto <span class="text-gray-400 font-normal">({{ fromAccount?.currency ?? '...' }})</span>
                        </label>
                        <input v-model="transferForm.amount" type="number" step="0.01" min="0.01" required class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" />
                        <p v-if="transferForm.errors.amount" class="text-rose-500 text-xs mt-1">{{ transferForm.errors.amount }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Comisión <span class="text-gray-400 font-normal">({{ fromAccount?.currency ?? '...' }})</span>
                        </label>
                        <input v-model="transferForm.commission" type="number" step="0.01" min="0" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" />
                    </div>
                </div>

                <!-- Conversion preview -->
                <div v-if="convertedAmount" class="flex items-center gap-2 bg-indigo-50 rounded-xl px-4 py-3 text-sm text-indigo-700">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    El destino recibirá aproximadamente <strong class="ml-1">{{ convertedAmount }}</strong> (cotización {{ formatMoney(usdRate) }}/USD)
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                    <input v-model="transferForm.notes" type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="Ej: Ahorro mensual" />
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="showTransferModal = false" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors">Cancelar</button>
                    <button type="submit" :disabled="transferForm.processing" class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg shadow-indigo-200/50 disabled:opacity-50">
                        Transferir
                    </button>
                </div>
            </form>
        </Modal>

        <Modal :show="showModal" @close="showModal = false" :title="editing ? 'Editar Cuenta' : 'Nueva Cuenta'">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Titular <span class="text-red-500">*</span></label>
                    <select v-model="form.user_id" required class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="" disabled>Selecciona un miembro</option>
                        <option v-for="u in familyUsers" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                    <p v-if="form.errors.user_id" class="text-rose-500 text-xs mt-1">{{ form.errors.user_id }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                    <input v-model="form.name" type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Ej: Banco Galicia" />
                    <p v-if="form.errors.name" class="text-rose-500 text-xs mt-1">{{ form.errors.name }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                        <select v-model="form.type" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                            <option v-for="(label, key) in accountTypes" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Institucion</label>
                        <input v-model="form.institution" type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="Ej: Galicia" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div v-if="!editing">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Saldo inicial</label>
                        <input v-model="form.balance" type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div v-if="editing" class="bg-gray-50 rounded-xl px-4 py-2.5 border border-gray-200">
                        <p class="text-xs text-gray-500 mb-0.5">Saldo actual</p>
                        <p class="text-sm font-semibold text-gray-800">{{ formatMoney(editing.balance, editing.currency) }}</p>
                        <p class="text-xs text-gray-400">Se actualiza automáticamente</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Moneda</label>
                        <select v-model="form.currency" :disabled="!!editing" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-500">
                            <option value="ARS">ARS</option><option value="USD">USD</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                    <input v-model="form.color" type="color" class="w-16 h-10 border border-gray-300 rounded-xl cursor-pointer" />
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="showModal = false" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors">Cancelar</button>
                    <button type="submit" :disabled="form.processing" class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg shadow-indigo-200/50 hover:shadow-xl transition-all disabled:opacity-50">
                        {{ editing ? 'Guardar' : 'Crear' }}
                    </button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
