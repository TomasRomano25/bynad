<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import MonthSelector from '@/Components/UI/MonthSelector.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { formatMoney } from '@/helpers';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const props = defineProps({
    stats: Object,
    expensesByCategory: Array,
    monthlyEvolution: Array,
    expensesByUser: Array,
    budgets: Array,
    accounts: Array,
    familyUsers: Array,
    familyCode: Number,
    filters: Object,
    usdRate: Number,
});

// Tabs
const activeTab = ref('dashboard');

// Auth user
const page = usePage();
const authUser = computed(() => page.props.auth.user);

// Profile form
const profileForm = useForm({
    name:   authUser.value?.name   ?? '',
    email:  authUser.value?.email  ?? '',
    age:    authUser.value?.age    ?? '',
    avatar: null,
});
const avatarPreview = ref(authUser.value?.avatar ? `/storage/${authUser.value.avatar}` : null);

const onAvatarChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    profileForm.avatar = file;
    avatarPreview.value = URL.createObjectURL(file);
};

const saveProfile = () => {
    profileForm.post(route('profile.update'), {
        method: 'patch',
        forceFormData: true,
        onSuccess: () => { profileForm.avatar = null; },
    });
};

// Password form
const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' });
const savePassword = () => {
    passwordForm.put(route('password.update'), {
        onSuccess: () => passwordForm.reset(),
    });
};

// Family code copy
const codeCopied = ref(false);
const copyCode = () => {
    navigator.clipboard.writeText(String(props.familyCode));
    codeCopied.value = true;
    setTimeout(() => codeCopied.value = false, 2000);
};

const evolutionChart = ref(null);
const categoryChart = ref(null);
const userChart = ref(null);
const necessaryChart = ref(null);

const filterUser = (userId) => {
    router.get(route('dashboard'), { ...props.filters, filter_user: userId }, { preserveState: true });
};

onMounted(() => {
    if (evolutionChart.value) {
        new Chart(evolutionChart.value, {
            type: 'line',
            data: {
                labels: props.monthlyEvolution.map(m => m.month),
                datasets: [
                    {
                        label: 'Ingresos',
                        data: props.monthlyEvolution.map(m => m.ingresos),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true, tension: 0.4, borderWidth: 2,
                    },
                    {
                        label: 'Gastos',
                        data: props.monthlyEvolution.map(m => m.gastos),
                        borderColor: '#f43f5e',
                        backgroundColor: 'rgba(244, 63, 94, 0.1)',
                        fill: true, tension: 0.4, borderWidth: 2,
                    },
                ],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true, ticks: { callback: v => '$ ' + v.toLocaleString() } } },
            },
        });
    }

    if (categoryChart.value && props.expensesByCategory.length) {
        const colors = ['#6366f1', '#8b5cf6', '#a78bfa', '#c4b5fd', '#10b981', '#f59e0b', '#ef4444', '#06b6d4', '#ec4899', '#84cc16'];
        new Chart(categoryChart.value, {
            type: 'doughnut',
            data: {
                labels: props.expensesByCategory.map(c => c.category || 'Sin categoria'),
                datasets: [{ data: props.expensesByCategory.map(c => c.total), backgroundColor: colors, borderWidth: 0 }],
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true } } } },
        });
    }

    if (userChart.value && props.expensesByUser.length) {
        new Chart(userChart.value, {
            type: 'bar',
            data: {
                labels: props.expensesByUser.map(u => u.name),
                datasets: [{ label: 'Gastos', data: props.expensesByUser.map(u => u.total), backgroundColor: ['#6366f1', '#8b5cf6', '#a78bfa'], borderRadius: 8 }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
        });
    }

    if (necessaryChart.value) {
        new Chart(necessaryChart.value, {
            type: 'doughnut',
            data: {
                labels: ['Necesarios', 'Gastos al pedo'],
                datasets: [{ data: [props.stats.necessaryExpenses, props.stats.unnecessaryExpenses], backgroundColor: ['#10b981', '#f43f5e'], borderWidth: 0 }],
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true } } } },
        });
    }
});
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Tabs -->
            <div class="flex items-center gap-1 border-b border-gray-200">
                <button @click="activeTab = 'dashboard'"
                    :class="['px-4 py-2.5 text-sm font-medium border-b-2 transition-colors', activeTab === 'dashboard' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
                    Dashboard
                </button>
                <button @click="activeTab = 'profile'"
                    :class="['px-4 py-2.5 text-sm font-medium border-b-2 transition-colors', activeTab === 'profile' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
                    Mi Perfil
                </button>
            </div>

            <!-- ── PERFIL TAB ── -->
            <div v-if="activeTab === 'profile'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Codigo de familia -->
                <div class="lg:col-span-3 bg-gradient-to-r from-indigo-50 to-purple-50 border border-indigo-100 rounded-2xl p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold text-indigo-400 uppercase tracking-wider mb-1">Codigo de familia</p>
                        <p class="text-4xl font-black text-indigo-700 tracking-widest">{{ familyCode ?? '—' }}</p>
                        <p class="text-sm text-gray-500 mt-1">Compartí este código con tu pareja o familia para que se unan a tu cuenta.</p>
                    </div>
                    <button @click="copyCode"
                        :class="['flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all', codeCopied ? 'bg-green-500 text-white' : 'bg-indigo-600 text-white hover:bg-indigo-700']">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-if="!codeCopied" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ codeCopied ? 'Copiado!' : 'Copiar codigo' }}
                    </button>
                </div>

                <!-- Miembros de la familia -->
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6">
                    <h2 class="text-sm font-semibold text-gray-700 mb-4">Miembros</h2>
                    <div class="space-y-3">
                        <div v-for="member in familyUsers" :key="member.id" class="flex items-center gap-3">
                            <div v-if="member.avatar" class="w-9 h-9 rounded-full overflow-hidden flex-shrink-0">
                                <img :src="`/storage/${member.avatar}`" class="w-full h-full object-cover" />
                            </div>
                            <div v-else class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center flex-shrink-0">
                                <span class="text-white text-sm font-bold">{{ member.name.charAt(0).toUpperCase() }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ member.name }}</p>
                                <p class="text-xs text-gray-400">{{ member.email }}</p>
                            </div>
                            <span v-if="member.id === authUser?.id" class="ml-auto text-xs bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded-full">Vos</span>
                        </div>
                    </div>
                </div>

                <!-- Editar perfil -->
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 space-y-5">
                    <h2 class="text-sm font-semibold text-gray-700">Editar perfil</h2>

                    <!-- Avatar -->
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-full overflow-hidden bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center flex-shrink-0">
                            <img v-if="avatarPreview" :src="avatarPreview" class="w-full h-full object-cover" />
                            <span v-else class="text-white text-xl font-bold">{{ authUser?.name?.charAt(0)?.toUpperCase() }}</span>
                        </div>
                        <div>
                            <label class="cursor-pointer text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                                Cambiar foto
                                <input type="file" accept="image/*" class="hidden" @change="onAvatarChange" />
                            </label>
                            <p class="text-xs text-gray-400 mt-0.5">JPG, PNG hasta 2MB</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-xs text-gray-500 mb-1.5">Nombre</label>
                            <input v-model="profileForm.name" type="text"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                            <p v-if="profileForm.errors.name" class="text-red-500 text-xs mt-1">{{ profileForm.errors.name }}</p>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs text-gray-500 mb-1.5">Email</label>
                            <input v-model="profileForm.email" type="email"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                            <p v-if="profileForm.errors.email" class="text-red-500 text-xs mt-1">{{ profileForm.errors.email }}</p>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1.5">Edad</label>
                            <input v-model="profileForm.age" type="number" min="1" max="120"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                            <p v-if="profileForm.errors.age" class="text-red-500 text-xs mt-1">{{ profileForm.errors.age }}</p>
                        </div>
                    </div>

                    <button @click="saveProfile" :disabled="profileForm.processing"
                        class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50">
                        {{ profileForm.processing ? 'Guardando...' : 'Guardar cambios' }}
                    </button>
                </div>

                <!-- Cambiar contraseña -->
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 space-y-4">
                    <h2 class="text-sm font-semibold text-gray-700">Cambiar contraseña</h2>

                    <div>
                        <label class="block text-xs text-gray-500 mb-1.5">Contraseña actual</label>
                        <input v-model="passwordForm.current_password" type="password"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                        <p v-if="passwordForm.errors.current_password" class="text-red-500 text-xs mt-1">{{ passwordForm.errors.current_password }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1.5">Nueva contraseña</label>
                        <input v-model="passwordForm.password" type="password"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                        <p v-if="passwordForm.errors.password" class="text-red-500 text-xs mt-1">{{ passwordForm.errors.password }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1.5">Confirmar nueva contraseña</label>
                        <input v-model="passwordForm.password_confirmation" type="password"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>

                    <button @click="savePassword" :disabled="passwordForm.processing"
                        class="w-full py-2.5 bg-gray-800 hover:bg-gray-900 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50">
                        {{ passwordForm.processing ? 'Cambiando...' : 'Cambiar contraseña' }}
                    </button>
                </div>
            </div>

            <!-- ── DASHBOARD TAB ── -->
            <template v-if="activeTab === 'dashboard'">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
                    <p class="text-sm text-gray-500 mt-1">Resumen de tus finanzas familiares</p>
                </div>
                <div class="flex items-center gap-3 flex-wrap">
                    <MonthSelector :month="filters.month" :year="filters.year" route-name="dashboard" />
                    <select @change="filterUser($event.target.value)" :value="filters.filter_user"
                        class="text-sm border border-gray-200 rounded-xl px-3 py-2 bg-white shadow-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="all">Toda la familia</option>
                        <option v-for="u in familyUsers" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard title="Ingresos del mes" :value="formatMoney(stats.totalIncomes)" :subtitle="formatMoney(stats.totalIncomesUsd, 'USD')"
                    icon="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" color="emerald" />
                <StatCard title="Gastos totales" :value="formatMoney(stats.totalExpenses)" :subtitle="formatMoney(stats.totalExpensesUsd, 'USD')"
                    icon="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" color="rose" />
                <StatCard title="Balance" :value="formatMoney(stats.balance)" :subtitle="formatMoney(stats.balanceUsd, 'USD')"
                    icon="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"
                    :color="stats.balance >= 0 ? 'indigo' : 'rose'" />
                <StatCard title="Patrimonio" :value="formatMoney(stats.totalAssets)" :subtitle="formatMoney(stats.totalAssetsUsd, 'USD')"
                    icon="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" color="purple" />
            </div>

            <div v-if="stats.unnecessaryExpenses > 0" class="bg-gradient-to-r from-rose-50 to-orange-50 border border-rose-200 rounded-2xl p-5">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-rose-100 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-rose-800">Gastos al pedo este mes</h3>
                        <p class="text-sm text-rose-600 mt-1">
                            Gastaste <strong>{{ formatMoney(stats.unnecessaryExpenses) }}</strong> en gastos innecesarios.
                            <span v-if="stats.unnecessaryTrend > 0" class="text-rose-700"> Subieron un {{ stats.unnecessaryTrend }}% vs el mes pasado. Hay que bajar esos gastos!</span>
                            <span v-else-if="stats.unnecessaryTrend < 0" class="text-emerald-700"> Bajaron un {{ Math.abs(stats.unnecessaryTrend) }}% vs el mes pasado. Bien ahi!</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800 mb-4">Evolucion mensual</h3>
                    <div class="h-64"><canvas ref="evolutionChart"></canvas></div>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800 mb-4">Gastos por categoria</h3>
                    <div class="h-64"><canvas ref="categoryChart"></canvas></div>
                    <p v-if="!expensesByCategory.length" class="text-sm text-gray-400 text-center py-12">Sin datos este mes</p>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800 mb-4">Gastos por persona</h3>
                    <div class="h-64"><canvas ref="userChart"></canvas></div>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800 mb-4">Necesarios vs Gastos al pedo</h3>
                    <div class="h-64"><canvas ref="necessaryChart"></canvas></div>
                </div>
            </div>

            <div v-if="budgets.length" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <h3 class="text-base font-semibold text-gray-800 mb-4">Presupuestos</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="budget in budgets" :key="budget.id" class="border border-gray-100 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">{{ budget.name }}</span>
                            <span :class="budget.percentage > 100 ? 'text-rose-600' : 'text-gray-500'" class="text-xs font-medium">{{ budget.percentage }}%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5 mb-2">
                            <div class="h-2.5 rounded-full transition-all duration-500"
                                :style="{ width: Math.min(budget.percentage, 100) + '%', backgroundColor: budget.percentage > 100 ? '#f43f5e' : budget.color }"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-400">
                            <span>{{ formatMoney(budget.spent) }}</span>
                            <span>{{ formatMoney(budget.amount) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
                    <p class="text-xs text-gray-500 mb-1">Gastos Fijos</p>
                    <p class="text-lg font-bold text-gray-800">{{ formatMoney(stats.totalFixedExpenses) }}</p>
                </div>
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
                    <p class="text-xs text-gray-500 mb-1">Variables</p>
                    <p class="text-lg font-bold text-gray-800">{{ formatMoney(stats.totalVariableExpenses) }}</p>
                </div>
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
                    <p class="text-xs text-gray-500 mb-1">Supermercado</p>
                    <p class="text-lg font-bold text-gray-800">{{ formatMoney(stats.totalSupermarket) }}</p>
                </div>
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
                    <p class="text-xs text-gray-500 mb-1">Tarjetas</p>
                    <p class="text-lg font-bold text-gray-800">{{ formatMoney(stats.totalCreditCard) }}</p>
                </div>
            </div>
            </template>
        </div>
    </AuthenticatedLayout>
</template>
