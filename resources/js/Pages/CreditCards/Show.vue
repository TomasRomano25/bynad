<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { formatMoney, cardBrands } from '@/helpers';

const props = defineProps({ card: Object, expenses: Array, usdRate: Number });

const showDeleteConfirm = ref(null);

const destroy = (expense) => {
    if (confirm('¿Eliminar este gasto?')) {
        useForm({}).delete(route('credit-cards.expenses.destroy', expense.id));
    }
};

const usagePercent = Math.min(Math.round((props.card.used_amount / props.card.limit_amount) * 100), 100);
const usageColor = usagePercent >= 90 ? '#ef4444' : usagePercent >= 70 ? '#f59e0b' : '#6366f1';

const totalRemaining = props.expenses.reduce((acc, e) => acc + e.installment_ars, 0);
</script>

<template>
    <Head :title="card.name" />
    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Back + title -->
            <div class="flex items-center gap-3">
                <Link :href="route('credit-cards.index')" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ card.name }}</h1>
                    <p class="text-sm text-gray-500">{{ card.bank }} · {{ cardBrands[card.brand] }} · **** {{ card.last_four || '0000' }}</p>
                </div>
            </div>

            <!-- Card visual + stats -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Card visual -->
                <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-xl" :style="{ background: `linear-gradient(135deg, ${card.color}, ${card.color}cc)` }">
                    <div class="absolute top-0 right-0 w-48 h-48 bg-white/10 rounded-full -translate-y-16 translate-x-16"></div>
                    <div class="absolute bottom-0 left-0 w-36 h-36 bg-white/5 rounded-full translate-y-12 -translate-x-12"></div>
                    <div class="relative">
                        <div class="flex items-start justify-between mb-8">
                            <div>
                                <p class="text-white/60 text-xs uppercase tracking-wider">{{ card.bank || 'Tarjeta' }}</p>
                                <p class="text-xl font-bold mt-1">{{ card.name }}</p>
                            </div>
                            <span class="text-sm font-bold uppercase bg-white/20 px-3 py-1 rounded-full">{{ cardBrands[card.brand] }}</span>
                        </div>
                        <div class="flex items-center gap-2 mb-6">
                            <div class="w-10 h-7 bg-gradient-to-br from-yellow-300 to-yellow-500 rounded-md"></div>
                            <span class="text-white/60 font-mono tracking-widest">**** **** **** {{ card.last_four || '0000' }}</span>
                        </div>
                        <div class="flex items-end justify-between">
                            <div>
                                <p class="text-white/60 text-xs">Titular</p>
                                <p class="font-medium">{{ card.user?.name }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-white/60 text-xs">Cierre {{ card.closing_day || '-' }} | Vto {{ card.due_day || '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div class="space-y-4">
                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
                            <p class="text-xs text-gray-500 mb-1">Límite</p>
                            <p class="text-lg font-bold text-gray-800">{{ formatMoney(card.limit_amount) }}</p>
                        </div>
                        <div class="bg-rose-50 rounded-2xl p-4 border border-rose-100 text-center">
                            <p class="text-xs text-rose-500 mb-1">Usado</p>
                            <p class="text-lg font-bold text-rose-600">{{ formatMoney(card.used_amount) }}</p>
                        </div>
                        <div class="bg-emerald-50 rounded-2xl p-4 border border-emerald-100 text-center">
                            <p class="text-xs text-emerald-500 mb-1">Disponible</p>
                            <p class="text-lg font-bold text-emerald-600">{{ formatMoney(card.available) }}</p>
                        </div>
                    </div>

                    <!-- Usage bar -->
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-600">Uso del límite</span>
                            <span class="text-sm font-bold" :style="{ color: usageColor }">{{ usagePercent }}%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-3">
                            <div class="h-3 rounded-full transition-all duration-500" :style="{ width: usagePercent + '%', backgroundColor: usageColor }"></div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">{{ formatMoney(card.used_amount) }} de {{ formatMoney(card.limit_amount) }} utilizados este mes</p>
                    </div>

                    <!-- Summary -->
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-500">Cuotas pendientes (ARS)</p>
                                <p class="text-xl font-bold text-gray-800 mt-0.5">{{ formatMoney(totalRemaining) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500">Compras activas</p>
                                <p class="text-xl font-bold text-indigo-600 mt-0.5">{{ expenses.length }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expenses with installment progress -->
            <div class="space-y-3">
                <h2 class="text-base font-semibold text-gray-700">Gastos y cuotas</h2>

                <div v-if="!expenses.length" class="bg-white rounded-2xl p-10 text-center shadow-sm border border-gray-100">
                    <p class="text-gray-400">No hay gastos cargados en esta tarjeta</p>
                </div>

                <div v-for="expense in expenses" :key="expense.id"
                    class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="font-semibold text-gray-800">{{ expense.description }}</h3>
                                <span v-if="expense.category" class="text-xs bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-full">{{ expense.category }}</span>
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full"
                                    :class="expense.currency === 'USD' ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">
                                    {{ expense.currency ?? 'ARS' }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-400 mt-0.5">Compra: {{ new Date(expense.purchase_date).toLocaleDateString('es-AR') }}</p>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <button @click="destroy(expense)" class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Amounts row -->
                    <div class="grid grid-cols-3 gap-3 mt-4">
                        <div class="text-center">
                            <p class="text-xs text-gray-400">Monto total</p>
                            <p class="text-sm font-bold text-gray-800">{{ formatMoney(expense.amount, expense.currency ?? 'ARS') }}</p>
                            <p v-if="(expense.currency ?? 'ARS') === 'USD'" class="text-xs text-gray-400">≈ {{ formatMoney(expense.amount_ars) }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-gray-400">Cuota</p>
                            <p class="text-sm font-bold text-indigo-600">{{ formatMoney(expense.installment_amount, expense.currency ?? 'ARS') }}/mes</p>
                            <p v-if="(expense.currency ?? 'ARS') === 'USD'" class="text-xs text-gray-400">≈ {{ formatMoney(expense.installment_ars) }}/mes</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-gray-400">Resta pagar</p>
                            <p class="text-sm font-bold text-amber-600">{{ formatMoney(expense.amount_remaining, expense.currency ?? 'ARS') }}</p>
                        </div>
                    </div>

                    <!-- Installment progress -->
                    <div class="mt-4">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs text-gray-500">
                                Cuota <span class="font-semibold text-gray-700">{{ expense.current_installment }}</span> de <span class="font-semibold text-gray-700">{{ expense.total_installments }}</span>
                            </span>
                            <span class="text-xs font-semibold" :class="expense.percent_paid >= 100 ? 'text-emerald-600' : 'text-indigo-600'">
                                {{ expense.percent_paid }}% pagado
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5">
                            <div class="h-2.5 rounded-full transition-all duration-500"
                                :class="expense.percent_paid >= 100 ? 'bg-emerald-500' : 'bg-gradient-to-r from-indigo-500 to-purple-600'"
                                :style="{ width: expense.percent_paid + '%' }">
                            </div>
                        </div>
                        <div class="flex justify-between mt-1">
                            <span class="text-xs text-gray-400">{{ expense.paid_installments }} cuota{{ expense.paid_installments !== 1 ? 's' : '' }} abonada{{ expense.paid_installments !== 1 ? 's' : '' }}</span>
                            <span class="text-xs text-gray-400">{{ expense.remaining_installments }} restante{{ expense.remaining_installments !== 1 ? 's' : '' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
