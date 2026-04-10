<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/UI/Modal.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { formatMoney, cardBrands } from '@/helpers';

const props = defineProps({ cards: Array, familyUsers: Array, usdRate: Number });

const showCardModal = ref(false);
const showExpenseModal = ref(false);
const editingCard = ref(null);
const selectedCard = ref(null);
const editingExpense = ref(null);

const cardForm = useForm({ user_id: '', name: '', brand: 'visa', last_four: '', bank: '', limit_amount: 0, closing_day: null, due_day: null, color: '#8b5cf6' });
const expenseForm = useForm({ description: '', amount: 0, currency: 'ARS', total_installments: 1, current_installment: 1, purchase_date: new Date().toISOString().split('T')[0], category: '' });

const openCreateCard = () => { editingCard.value = null; cardForm.reset(); cardForm.user_id = props.familyUsers?.[0]?.id ?? ''; showCardModal.value = true; };
const openEditCard = (card) => {
    editingCard.value = card;
    Object.assign(cardForm, { user_id: card.user_id, name: card.name, brand: card.brand, last_four: card.last_four, bank: card.bank, limit_amount: card.limit_amount, closing_day: card.closing_day, due_day: card.due_day, color: card.color });
    showCardModal.value = true;
};

const submitCard = () => {
    if (editingCard.value) {
        cardForm.put(route('credit-cards.update', editingCard.value.id), { onSuccess: () => { showCardModal.value = false; } });
    } else {
        cardForm.post(route('credit-cards.store'), { onSuccess: () => { showCardModal.value = false; cardForm.reset(); } });
    }
};

const destroyCard = (card) => { if (confirm('Eliminar esta tarjeta?')) useForm({}).delete(route('credit-cards.destroy', card.id)); };

const openAddExpense = (card) => { selectedCard.value = card; editingExpense.value = null; expenseForm.reset(); expenseForm.purchase_date = new Date().toISOString().split('T')[0]; showExpenseModal.value = true; };
const openEditExpense = (card, expense) => {
    selectedCard.value = card; editingExpense.value = expense;
    Object.assign(expenseForm, { description: expense.description, amount: expense.amount, currency: expense.currency ?? 'ARS', total_installments: expense.total_installments, current_installment: expense.current_installment, purchase_date: expense.purchase_date?.split('T')[0], category: expense.category });
    showExpenseModal.value = true;
};

const submitExpense = () => {
    if (editingExpense.value) {
        expenseForm.put(route('credit-cards.expenses.update', editingExpense.value.id), { onSuccess: () => { showExpenseModal.value = false; } });
    } else {
        expenseForm.post(route('credit-cards.expenses.store', selectedCard.value.id), { onSuccess: () => { showExpenseModal.value = false; expenseForm.reset(); } });
    }
};

const destroyExpense = (expense) => { if (confirm('Eliminar gasto?')) useForm({}).delete(route('credit-cards.expenses.destroy', expense.id)); };

const brandLogos = { visa: '#1a1f71', mastercard: '#eb001b', amex: '#006fcf', naranja: '#ff6600', cabal: '#00529b', otro: '#6b7280' };
</script>

<template>
    <Head title="Tarjetas de Credito" />
    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Tarjetas de Credito</h1>
                    <p class="text-sm text-gray-500 mt-1">Gestiona tus tarjetas y sus consumos</p>
                </div>
                <button @click="openCreateCard" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-sm font-medium rounded-xl shadow-lg shadow-indigo-200/50 hover:shadow-xl transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Nueva Tarjeta
                </button>
            </div>

            <div v-if="!cards.length" class="bg-white rounded-2xl p-12 text-center shadow-sm border border-gray-100">
                <p class="text-gray-500">No tenes tarjetas cargadas</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div v-for="card in cards" :key="card.id" class="space-y-4">
                    <!-- Credit Card Visual -->
                    <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-xl" :style="{ background: `linear-gradient(135deg, ${card.color}, ${card.color}dd)` }">
                        <div class="absolute top-0 right-0 w-40 h-40 bg-white/10 rounded-full -translate-y-10 translate-x-10"></div>
                        <div class="absolute bottom-0 left-0 w-32 h-32 bg-white/5 rounded-full translate-y-10 -translate-x-10"></div>
                        <div class="relative">
                            <div class="flex items-start justify-between mb-8">
                                <div>
                                    <p class="text-white/70 text-xs font-medium uppercase tracking-wider">{{ card.bank || 'Tarjeta' }}</p>
                                    <p class="text-lg font-bold mt-1">{{ card.name }}</p>
                                </div>
                                <div class="flex gap-1">
                                    <button @click="openEditCard(card)" class="p-1.5 bg-white/20 rounded-lg hover:bg-white/30 transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
                                    <button @click="destroyCard(card)" class="p-1.5 bg-white/20 rounded-lg hover:bg-white/30 transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 mb-6">
                                <div class="w-10 h-7 bg-gradient-to-br from-yellow-300 to-yellow-500 rounded-md"></div>
                                <span class="text-white/60 font-mono text-sm tracking-widest">**** **** **** {{ card.last_four || '0000' }}</span>
                            </div>
                            <div class="flex items-end justify-between">
                                <div>
                                    <p class="text-white/60 text-xs">Titular</p>
                                    <p class="text-sm font-medium">{{ card.user?.name }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-white/60 text-xs">Cierre: {{ card.closing_day || '-' }} | Vto: {{ card.due_day || '-' }}</p>
                                    <p class="text-sm font-bold uppercase">{{ cardBrands[card.brand] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card info -->
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-xs text-gray-500">Limite</p>
                                <p class="font-bold text-gray-800">{{ formatMoney(card.limit_amount) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500">Usado</p>
                                <p class="font-bold text-rose-600">{{ formatMoney(card.used_amount) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500">Disponible</p>
                                <p class="font-bold text-emerald-600">{{ formatMoney(card.available) }}</p>
                            </div>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="h-2 rounded-full bg-gradient-to-r from-indigo-500 to-purple-600 transition-all" :style="{ width: Math.min((card.used_amount / card.limit_amount * 100), 100) + '%' }"></div>
                        </div>

                        <div class="mt-4 flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-gray-700">Gastos</h4>
                            <button @click="openAddExpense(card)" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">+ Agregar gasto</button>
                        </div>
                        <div v-if="card.expenses?.length" class="mt-3 space-y-2">
                            <div v-for="expense in card.expenses" :key="expense.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                                <div>
                                    <p class="text-sm font-medium text-gray-700">{{ expense.description }}</p>
                                    <p class="text-xs text-gray-400">Cuota {{ expense.current_installment }}/{{ expense.total_installments }} - {{ formatMoney(expense.installment_amount, expense.currency ?? 'ARS') }}/mes</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-gray-800">{{ formatMoney(expense.amount, expense.currency ?? 'ARS') }}</span>
                                    <button @click="openEditExpense(card, expense)" class="p-1 text-gray-400 hover:text-indigo-600"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
                                    <button @click="destroyExpense(expense)" class="p-1 text-gray-400 hover:text-rose-600"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-xs text-gray-400 mt-3 text-center py-2">Sin gastos cargados</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Modal -->
        <Modal :show="showCardModal" @close="showCardModal = false" :title="editingCard ? 'Editar Tarjeta' : 'Nueva Tarjeta'">
            <form @submit.prevent="submitCard" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Titular <span class="text-red-500">*</span></label>
                    <select v-model="cardForm.user_id" required class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="" disabled>Selecciona un miembro</option>
                        <option v-for="u in familyUsers" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                    <p v-if="cardForm.errors.user_id" class="text-red-500 text-xs mt-1">{{ cardForm.errors.user_id }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label><input v-model="cardForm.name" type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="Mi Visa" /></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Marca</label><select v-model="cardForm.brand" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500"><option v-for="(l,k) in cardBrands" :key="k" :value="k">{{ l }}</option></select></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Ultimos 4 digitos</label><input v-model="cardForm.last_four" type="text" maxlength="4" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" /></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Banco</label><input v-model="cardForm.bank" type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" /></div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Limite</label><input v-model="cardForm.limit_amount" type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" /></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Dia cierre</label><input v-model="cardForm.closing_day" type="number" min="1" max="31" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" /></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Dia vto</label><input v-model="cardForm.due_day" type="number" min="1" max="31" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" /></div>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Color</label><input v-model="cardForm.color" type="color" class="w-16 h-10 border border-gray-300 rounded-xl cursor-pointer" /></div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="showCardModal = false" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200">Cancelar</button>
                    <button type="submit" :disabled="cardForm.processing" class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg disabled:opacity-50">{{ editingCard ? 'Guardar' : 'Crear' }}</button>
                </div>
            </form>
        </Modal>

        <!-- Expense Modal -->
        <Modal :show="showExpenseModal" @close="showExpenseModal = false" :title="editingExpense ? 'Editar Gasto' : 'Nuevo Gasto de Tarjeta'">
            <form @submit.prevent="submitExpense" class="space-y-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Descripcion</label><input v-model="expenseForm.description" type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" /></div>
                <div class="grid grid-cols-3 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Monto total</label><input v-model="expenseForm.amount" type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" /></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Moneda</label><select v-model="expenseForm.currency" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500"><option value="ARS">ARS</option><option value="USD">USD</option></select></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Fecha compra</label><input v-model="expenseForm.purchase_date" type="date" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" /></div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Total cuotas</label><input v-model="expenseForm.total_installments" type="number" min="1" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" /></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Cuota actual</label><input v-model="expenseForm.current_installment" type="number" min="1" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" /></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label><input v-model="expenseForm.category" type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" /></div>
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="showExpenseModal = false" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200">Cancelar</button>
                    <button type="submit" :disabled="expenseForm.processing" class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg disabled:opacity-50">{{ editingExpense ? 'Guardar' : 'Agregar' }}</button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
