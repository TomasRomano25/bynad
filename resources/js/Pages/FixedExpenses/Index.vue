<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/UI/Modal.vue';
import MonthSelector from '@/Components/UI/MonthSelector.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { formatMoney, fixedExpenseCategories } from '@/helpers';

const props = defineProps({ expenses: Array, accounts: Array, familyUsers: Array, filters: Object, usdRate: Number });
const showModal = ref(false);
const editing = ref(null);
const form = useForm({ user_id: '', name: '', amount: 0, currency: 'ARS', account_id: null, due_day: null, category: '', notes: '' });

const openCreate = () => { editing.value = null; form.reset(); form.user_id = props.familyUsers?.[0]?.id ?? ''; form.currency = 'ARS'; showModal.value = true; };
const openEdit = (e) => {
    editing.value = e;
    Object.assign(form, { user_id: e.user_id, name: e.name, amount: e.amount, currency: e.currency ?? 'ARS', account_id: e.account_id, due_day: e.due_day, category: e.category, notes: e.notes });
    showModal.value = true;
};

const submit = () => {
    if (editing.value) {
        form.put(route('fixed-expenses.update', editing.value.id), { onSuccess: () => showModal.value = false });
    } else {
        form.post(route('fixed-expenses.store'), { onSuccess: () => { showModal.value = false; form.reset(); } });
    }
};

const destroy = (e) => { if (confirm('Eliminar?')) useForm({}).delete(route('fixed-expenses.destroy', e.id)); };

const togglePayment = (expense) => {
    router.post(route('fixed-expenses.toggle-payment', expense.id), {
        month: props.filters.month, year: props.filters.year, account_id: expense.account_id,
    });
};

const totalExpenses = () => props.expenses.reduce((acc, e) => acc + parseFloat(e.amount), 0);
const totalPaid = () => props.expenses.filter(e => e.is_paid).reduce((acc, e) => acc + parseFloat(e.amount), 0);
const totalPending = () => totalExpenses() - totalPaid();
</script>

<template>
    <Head title="Gastos Fijos" />
    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Gastos Fijos</h1>
                    <p class="text-sm text-gray-500 mt-1">Controla tus gastos fijos mensuales</p>
                </div>
                <div class="flex items-center gap-3">
                    <MonthSelector :month="filters.month" :year="filters.year" route-name="fixed-expenses.index" />
                    <button @click="openCreate" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-sm font-medium rounded-xl shadow-lg shadow-indigo-200/50 hover:shadow-xl transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Nuevo
                    </button>
                </div>
            </div>

            <!-- Summary -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 text-center">
                    <p class="text-xs text-gray-500 mb-1">Total Gastos Fijos</p>
                    <p class="text-xl font-bold text-gray-800">{{ formatMoney(totalExpenses()) }}</p>
                </div>
                <div class="bg-emerald-50 rounded-2xl p-5 border border-emerald-100 text-center">
                    <p class="text-xs text-emerald-600 mb-1">Pagados</p>
                    <p class="text-xl font-bold text-emerald-700">{{ formatMoney(totalPaid()) }}</p>
                </div>
                <div class="bg-amber-50 rounded-2xl p-5 border border-amber-100 text-center">
                    <p class="text-xs text-amber-600 mb-1">Pendientes</p>
                    <p class="text-xl font-bold text-amber-700">{{ formatMoney(totalPending()) }}</p>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead><tr class="border-b border-gray-100">
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Estado</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Gasto</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Monto</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Cuenta</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Vence</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Persona</th>
                            <th class="text-right px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Acciones</th>
                        </tr></thead>
                        <tbody>
                            <tr v-for="expense in expenses" :key="expense.id" class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <button @click="togglePayment(expense)" :class="expense.is_paid ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-400'" class="w-8 h-8 rounded-full flex items-center justify-center transition-colors hover:scale-110">
                                        <svg v-if="expense.is_paid" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </button>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-gray-800">{{ expense.name }}</p>
                                    <p v-if="expense.category" class="text-xs text-gray-400">{{ expense.category }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-bold text-gray-800">{{ formatMoney(expense.amount, expense.currency ?? 'ARS') }}</p>
                                    <p class="text-xs text-gray-400">
                                        <template v-if="(expense.currency ?? 'ARS') === 'USD'">≈ {{ formatMoney(expense.amount * usdRate) }}</template>
                                        <template v-else>≈ {{ formatMoney(expense.amount_usd, 'USD') }}</template>
                                    </p>
                                </td>
                                <td class="px-6 py-4 hidden sm:table-cell"><span class="text-sm text-gray-600">{{ expense.account?.name || '-' }}</span></td>
                                <td class="px-6 py-4 hidden sm:table-cell"><span class="text-sm text-gray-600">Dia {{ expense.due_day || '-' }}</span></td>
                                <td class="px-6 py-4 hidden md:table-cell"><span class="text-sm text-gray-600">{{ expense.user?.name }}</span></td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-1">
                                        <button @click="openEdit(expense)" class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
                                        <button @click="destroy(expense)" class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="!expenses.length" class="p-12 text-center"><p class="text-gray-400">No hay gastos fijos cargados</p></div>
            </div>
        </div>

        <Modal :show="showModal" @close="showModal = false" :title="editing ? 'Editar Gasto Fijo' : 'Nuevo Gasto Fijo'">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Titular <span class="text-red-500">*</span></label>
                    <select v-model="form.user_id" required class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="" disabled>Selecciona un miembro</option>
                        <option v-for="u in familyUsers" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                    <p v-if="form.errors.user_id" class="text-rose-500 text-xs mt-1">{{ form.errors.user_id }}</p>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label><input v-model="form.name" type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="Ej: Alquiler" /></div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Moneda</label>
                        <div class="flex rounded-xl overflow-hidden border border-gray-300">
                            <button type="button" @click="form.currency = 'ARS'" :class="form.currency === 'ARS' ? 'bg-indigo-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'" class="flex-1 py-2.5 text-sm font-medium transition-colors">ARS</button>
                            <button type="button" @click="form.currency = 'USD'" :class="form.currency === 'USD' ? 'bg-indigo-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'" class="flex-1 py-2.5 text-sm font-medium transition-colors border-l border-gray-300">USD</button>
                        </div>
                    </div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Monto ({{ form.currency }})</label><input v-model="form.amount" type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" /></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Dia vencimiento</label><input v-model="form.due_day" type="number" min="1" max="31" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" /></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Cuenta</label><select v-model="form.account_id" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500"><option :value="null">Seleccionar...</option><option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option></select></div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                        <input v-model="form.category" type="text" list="fixed-categories" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="Elegir o escribir..." />
                        <datalist id="fixed-categories">
                            <option v-for="cat in fixedExpenseCategories" :key="cat" :value="cat" />
                        </datalist>
                    </div>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Notas</label><textarea v-model="form.notes" rows="2" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500"></textarea></div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="showModal = false" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200">Cancelar</button>
                    <button type="submit" :disabled="form.processing" class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg disabled:opacity-50">{{ editing ? 'Guardar' : 'Crear' }}</button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
