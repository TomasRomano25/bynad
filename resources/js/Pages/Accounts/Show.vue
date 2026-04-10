<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { formatMoney } from '@/helpers';

const props = defineProps({
    account: Object,
    movements: Array,
    usdRate: Number,
    summary: Object,
});

const typeFilter = ref('all');

const typeConfig = {
    income:         { label: 'Ingreso',      color: 'bg-emerald-100 text-emerald-700' },
    variable_expense: { label: 'Gasto Variable', color: 'bg-rose-100 text-rose-700' },
    fixed_expense:  { label: 'Gasto Fijo',   color: 'bg-amber-100 text-amber-700' },
    supermarket:    { label: 'Supermercado', color: 'bg-blue-100 text-blue-700' },
    transfer_in:    { label: 'Transferencia', color: 'bg-indigo-100 text-indigo-700' },
    transfer_out:   { label: 'Transferencia', color: 'bg-indigo-100 text-indigo-700' },
};

const filtered = computed(() => {
    if (typeFilter.value === 'all') return props.movements;
    if (typeFilter.value === 'income') return props.movements.filter(m => m.type === 'income');
    if (typeFilter.value === 'expense') return props.movements.filter(m => ['variable_expense','fixed_expense','supermarket'].includes(m.type));
    if (typeFilter.value === 'unnecessary') return props.movements.filter(m => m.necessary === false);
    if (typeFilter.value === 'transfer') return props.movements.filter(m => m.type.startsWith('transfer'));
    return props.movements;
});

const formatDate = (d) => {
    if (!d) return '';
    return new Date(d).toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
};
</script>

<template>
    <Head :title="'Cuenta: ' + account.name" />
    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link href="/accounts" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </Link>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-800">{{ account.name }}</h1>
                    <p class="text-sm text-gray-500 mt-0.5">{{ account.institution || account.type }} · {{ account.user?.name }}</p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold text-gray-800">{{ formatMoney(account.balance, account.currency) }}</p>
                    <p class="text-sm text-gray-400">
                        <template v-if="account.currency === 'USD'">≈ {{ formatMoney(account.balance * usdRate) }} ARS</template>
                        <template v-else>≈ {{ formatMoney(account.balance_usd, 'USD') }}</template>
                    </p>
                </div>
            </div>

            <!-- Summary cards -->
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-emerald-50 rounded-2xl p-4 border border-emerald-100 text-center">
                    <p class="text-xs text-emerald-600 mb-1">Total entradas</p>
                    <p class="text-lg font-bold text-emerald-700">{{ formatMoney(summary.total_in) }}</p>
                </div>
                <div class="bg-rose-50 rounded-2xl p-4 border border-rose-100 text-center">
                    <p class="text-xs text-rose-600 mb-1">Total salidas</p>
                    <p class="text-lg font-bold text-rose-700">{{ formatMoney(summary.total_out) }}</p>
                </div>
                <div class="bg-white rounded-2xl p-4 border border-gray-100 text-center">
                    <p class="text-xs text-gray-500 mb-1">Movimientos</p>
                    <p class="text-lg font-bold text-gray-800">{{ movements.length }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex gap-2 flex-wrap">
                <button v-for="(label, key) in { all: 'Todos', income: 'Ingresos', expense: 'Gastos', unnecessary: 'Gastos al pedo', transfer: 'Transferencias' }"
                    :key="key"
                    @click="typeFilter = key"
                    :class="typeFilter === key ? 'bg-indigo-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200'"
                    class="px-4 py-2 text-sm font-medium rounded-xl transition-colors">
                    {{ label }}
                </button>
            </div>

            <!-- Movements list -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div v-if="filtered.length" class="divide-y divide-gray-50">
                    <div v-for="m in filtered" :key="m.id" class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50/50 transition-colors">
                        <!-- Icon -->
                        <div :class="m.direction === '+' ? 'bg-emerald-100' : 'bg-rose-100'" class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg v-if="m.type === 'income'" class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            <svg v-else-if="m.type === 'supermarket'" class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" /></svg>
                            <svg v-else-if="m.type.startsWith('transfer')" class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                            <svg v-else class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>
                        </div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ m.description }}</p>
                                <span :class="typeConfig[m.type]?.color" class="text-xs px-2 py-0.5 rounded-full font-medium">{{ typeConfig[m.type]?.label }}</span>
                                <span v-if="m.necessary === false" class="text-xs px-2 py-0.5 rounded-full font-medium bg-orange-100 text-orange-600">Al pedo</span>
                                <span v-if="m.necessary === true && m.type === 'variable_expense'" class="text-xs px-2 py-0.5 rounded-full font-medium bg-green-100 text-green-600">Necesario</span>
                            </div>
                            <div class="flex items-center gap-3 mt-0.5">
                                <p class="text-xs text-gray-400">{{ formatDate(m.date) }}</p>
                                <p v-if="m.person" class="text-xs text-gray-400">· {{ m.person }}</p>
                                <p v-if="m.category" class="text-xs text-gray-400">· {{ m.category }}</p>
                            </div>
                        </div>

                        <!-- Amount -->
                        <div class="text-right flex-shrink-0">
                            <p :class="m.direction === '+' ? 'text-emerald-600' : 'text-rose-600'" class="text-sm font-bold">
                                {{ m.direction === '+' ? '+' : '-' }}{{ formatMoney(m.amount, m.currency) }}
                            </p>
                            <p v-if="m.currency !== 'ARS'" class="text-xs text-gray-400">
                                ≈ {{ formatMoney(m.amount_ars) }}
                            </p>
                        </div>
                    </div>
                </div>
                <div v-else class="p-12 text-center">
                    <p class="text-gray-400">No hay movimientos con ese filtro</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
