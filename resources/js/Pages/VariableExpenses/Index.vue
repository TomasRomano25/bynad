<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/UI/Modal.vue';
import MonthSelector from '@/Components/UI/MonthSelector.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { formatMoney } from '@/helpers';

const props = defineProps({ expenses: Array, accounts: Array, budgets: Array, familyUsers: Array, filters: Object, totals: Object, usdRate: Number });
const showModal = ref(false);
const showBudgetModal = ref(false);
const editing = ref(null);
const editingBudget = ref(null);

const form = useForm({ user_id: '', description: '', amount: 0, currency: 'ARS', account_id: null, budget_id: null, date: new Date().toISOString().split('T')[0], category: '', is_necessary: true, notes: '' });
const budgetForm = useForm({ name: '', amount: 0, period: 'mensual', color: '#10b981' });

const openCreate = () => { editing.value = null; form.reset(); form.user_id = props.familyUsers?.[0]?.id ?? ''; form.date = new Date().toISOString().split('T')[0]; form.currency = 'ARS'; form.is_necessary = true; showModal.value = true; };
const openEdit = (e) => {
    editing.value = e;
    Object.assign(form, { user_id: e.user_id, description: e.description, amount: e.amount, currency: e.currency ?? 'ARS', account_id: e.account_id, budget_id: e.budget_id, date: e.date?.split('T')[0], category: e.category, is_necessary: e.is_necessary, notes: e.notes });
    showModal.value = true;
};

const submit = () => {
    if (editing.value) form.put(route('variable-expenses.update', editing.value.id), { onSuccess: () => showModal.value = false });
    else form.post(route('variable-expenses.store'), { onSuccess: () => { showModal.value = false; form.reset(); } });
};
const destroy = (e) => { if (confirm('Eliminar?')) useForm({}).delete(route('variable-expenses.destroy', e.id)); };

const openCreateBudget = () => { editingBudget.value = null; budgetForm.reset(); showBudgetModal.value = true; };
const openEditBudget = (b) => {
    editingBudget.value = b;
    Object.assign(budgetForm, { name: b.name, amount: b.amount, period: b.period, color: b.color });
    showBudgetModal.value = true;
};
const submitBudget = () => {
    if (editingBudget.value) budgetForm.put(route('budgets.update', editingBudget.value.id), { onSuccess: () => showBudgetModal.value = false });
    else budgetForm.post(route('budgets.store'), { onSuccess: () => { showBudgetModal.value = false; budgetForm.reset(); } });
};
const destroyBudget = (b) => { if (confirm('Eliminar presupuesto?')) useForm({}).delete(route('budgets.destroy', b.id)); };

const applyFilter = (key, value) => {
    router.get(route('variable-expenses.index'), { ...props.filters, [key]: value }, { preserveState: true });
};
</script>

<template>
    <Head title="Gastos Variables" />
    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Gastos Variables</h1>
                    <p class="text-sm text-gray-500 mt-1">Registra y controla tus gastos del dia a dia</p>
                </div>
                <div class="flex items-center gap-3 flex-wrap">
                    <MonthSelector :month="filters.month" :year="filters.year" route-name="variable-expenses.index" />
                    <button @click="openCreate" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-sm font-medium rounded-xl shadow-lg hover:shadow-xl transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Nuevo Gasto
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex items-center gap-3 flex-wrap">
                <select @change="applyFilter('filter_user', $event.target.value)" :value="filters.filter_user" class="text-sm border border-gray-200 rounded-xl px-3 py-2 bg-white shadow-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="all">Todos</option>
                    <option v-for="u in familyUsers" :key="u.id" :value="u.id">{{ u.name }}</option>
                </select>
                <select @change="applyFilter('filter_budget', $event.target.value)" :value="filters.filter_budget" class="text-sm border border-gray-200 rounded-xl px-3 py-2 bg-white shadow-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="all">Todos los presupuestos</option>
                    <option v-for="b in budgets" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
                <select @change="applyFilter('filter_necessary', $event.target.value)" :value="filters.filter_necessary" class="text-sm border border-gray-200 rounded-xl px-3 py-2 bg-white shadow-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="all">Todos</option>
                    <option value="yes">Necesarios</option>
                    <option value="no">Gastos al pedo</option>
                </select>
            </div>

            <!-- Summary -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 text-center">
                    <p class="text-xs text-gray-500 mb-1">Total</p><p class="text-xl font-bold text-gray-800">{{ formatMoney(totals.total) }}</p>
                </div>
                <div class="bg-emerald-50 rounded-2xl p-5 border border-emerald-100 text-center">
                    <p class="text-xs text-emerald-600 mb-1">Necesarios</p><p class="text-xl font-bold text-emerald-700">{{ formatMoney(totals.necessary) }}</p>
                </div>
                <div class="bg-rose-50 rounded-2xl p-5 border border-rose-100 text-center">
                    <p class="text-xs text-rose-600 mb-1">Gastos al pedo</p><p class="text-xl font-bold text-rose-700">{{ formatMoney(totals.unnecessary) }}</p>
                </div>
            </div>

            <!-- Budgets -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-700">Presupuestos</h3>
                    <button @click="openCreateBudget" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">+ Nuevo presupuesto</button>
                </div>
                <div v-if="budgets.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div v-for="b in budgets" :key="b.id" class="border border-gray-100 rounded-xl p-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">{{ b.name }}</span>
                            <div class="flex items-center gap-1">
                                <span :class="b.percentage > 100 ? 'text-rose-600' : 'text-gray-400'" class="text-xs">{{ b.percentage }}%</span>
                                <button @click="openEditBudget(b)" class="p-1 text-gray-400 hover:text-indigo-600"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
                                <button @click="destroyBudget(b)" class="p-1 text-gray-400 hover:text-rose-600"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                            </div>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2 mb-1">
                            <div class="h-2 rounded-full transition-all" :style="{ width: Math.min(b.percentage, 100) + '%', backgroundColor: b.percentage > 100 ? '#f43f5e' : b.color }"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-400"><span>{{ formatMoney(b.spent) }}</span><span>{{ formatMoney(b.amount) }}</span></div>
                    </div>
                </div>
                <p v-else class="text-xs text-gray-400 text-center py-2">Sin presupuestos. Crea uno para controlar tus gastos.</p>
            </div>

            <!-- Expense list -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead><tr class="border-b border-gray-100">
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Descripcion</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Monto</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Cuenta</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Presupuesto</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Tipo</th>
                            <th class="text-right px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Acciones</th>
                        </tr></thead>
                        <tbody>
                            <tr v-for="e in expenses" :key="e.id" class="border-b border-gray-50 hover:bg-gray-50/50">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-gray-800">{{ e.description }}</p>
                                    <p class="text-xs text-gray-400">{{ e.user?.name }} - {{ e.date?.split('T')[0] }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-bold text-gray-800">{{ formatMoney(e.amount, e.currency ?? 'ARS') }}</p>
                                    <p class="text-xs text-gray-400">
                                        <template v-if="(e.currency ?? 'ARS') === 'USD'">≈ {{ formatMoney(e.amount * usdRate) }}</template>
                                        <template v-else>≈ {{ formatMoney(e.amount_usd, 'USD') }}</template>
                                    </p>
                                </td>
                                <td class="px-6 py-4 hidden sm:table-cell"><span class="text-sm text-gray-600">{{ e.account?.name || '-' }}</span></td>
                                <td class="px-6 py-4 hidden sm:table-cell"><span class="text-sm text-gray-600">{{ e.budget?.name || '-' }}</span></td>
                                <td class="px-6 py-4 hidden md:table-cell">
                                    <span :class="e.is_necessary ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                        {{ e.is_necessary ? 'Necesario' : 'Al pedo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-1">
                                        <button @click="openEdit(e)" class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
                                        <button @click="destroy(e)" class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="!expenses.length" class="p-12 text-center"><p class="text-gray-400">Sin gastos variables este mes</p></div>
            </div>
        </div>

        <!-- Expense Modal -->
        <Modal :show="showModal" @close="showModal = false" :title="editing ? 'Editar Gasto' : 'Nuevo Gasto Variable'">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Titular <span class="text-red-500">*</span></label>
                    <select v-model="form.user_id" required class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="" disabled>Selecciona un miembro</option>
                        <option v-for="u in familyUsers" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                    <p v-if="form.errors.user_id" class="text-rose-500 text-xs mt-1">{{ form.errors.user_id }}</p>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Descripcion</label><input v-model="form.description" type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" /></div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Moneda</label>
                        <div class="flex rounded-xl overflow-hidden border border-gray-300">
                            <button type="button" @click="form.currency = 'ARS'" :class="form.currency === 'ARS' ? 'bg-indigo-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'" class="flex-1 py-2.5 text-sm font-medium transition-colors">ARS</button>
                            <button type="button" @click="form.currency = 'USD'" :class="form.currency === 'USD' ? 'bg-indigo-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'" class="flex-1 py-2.5 text-sm font-medium transition-colors border-l border-gray-300">USD</button>
                        </div>
                    </div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Monto ({{ form.currency }})</label><input v-model="form.amount" type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" /></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label><input v-model="form.date" type="date" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" /></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Cuenta</label><select v-model="form.account_id" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500"><option :value="null">Seleccionar...</option><option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option></select></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Presupuesto</label><select v-model="form.budget_id" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500"><option :value="null">Sin presupuesto</option><option v-for="b in budgets" :key="b.id" :value="b.id">{{ b.name }}</option></select></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label><input v-model="form.category" type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="Ej: Comida" /></div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input v-model="form.is_necessary" type="checkbox" class="w-5 h-5 rounded-lg border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            <span class="text-sm text-gray-700">Necesario</span>
                        </label>
                    </div>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Notas</label><textarea v-model="form.notes" rows="2" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500"></textarea></div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="showModal = false" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200">Cancelar</button>
                    <button type="submit" :disabled="form.processing" class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg disabled:opacity-50">{{ editing ? 'Guardar' : 'Agregar' }}</button>
                </div>
            </form>
        </Modal>

        <!-- Budget Modal -->
        <Modal :show="showBudgetModal" @close="showBudgetModal = false" :title="editingBudget ? 'Editar Presupuesto' : 'Nuevo Presupuesto'">
            <form @submit.prevent="submitBudget" class="space-y-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label><input v-model="budgetForm.name" type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="Ej: Comida" /></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Monto</label><input v-model="budgetForm.amount" type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" /></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Periodo</label><select v-model="budgetForm.period" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500"><option value="mensual">Mensual</option><option value="semanal">Semanal</option><option value="anual">Anual</option></select></div>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Color</label><input v-model="budgetForm.color" type="color" class="w-16 h-10 border border-gray-300 rounded-xl cursor-pointer" /></div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="showBudgetModal = false" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200">Cancelar</button>
                    <button type="submit" :disabled="budgetForm.processing" class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg disabled:opacity-50">{{ editingBudget ? 'Guardar' : 'Crear' }}</button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
