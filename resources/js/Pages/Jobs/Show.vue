<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { formatMoney, formatDate } from '@/helpers';
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

const props = defineProps({
    job: Object, incomes: Array, monthly: Array,
    byAccount: Array, byCurrency: Array, byPerson: Array,
    yearly: Array, stats: Object, year: Number, usdRate: Number,
});

const monthChart = ref(null);
const accountChart = ref(null);
const yearlyChart = ref(null);

const monthLabels = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
const palette = ['#6366f1','#8b5cf6','#10b981','#f59e0b','#ef4444','#06b6d4','#ec4899','#14b8a6'];

const yearOptions = computed(() => {
    const cy = new Date().getFullYear();
    return [cy - 4, cy - 3, cy - 2, cy - 1, cy, cy + 1];
});
const changeYear = (y) => router.get(route('jobs.show', props.job.id), { year: y }, { preserveState: false });

onMounted(() => {
    if (monthChart.value) {
        new Chart(monthChart.value, {
            type: 'bar',
            data: { labels: monthLabels, datasets: [{ data: props.monthly, backgroundColor: props.job.color || '#10b981', borderRadius: 6 }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => formatMoney(ctx.raw) } } },
                scales: { y: { beginAtZero: true, ticks: { callback: v => formatMoney(v) } } },
            },
        });
    }
    if (accountChart.value && props.byAccount.length) {
        new Chart(accountChart.value, {
            type: 'doughnut',
            data: {
                labels: props.byAccount.map(a => a.account_name),
                datasets: [{ data: props.byAccount.map(a => a.total), backgroundColor: props.byAccount.map((a, i) => a.account_color || palette[i % palette.length]), borderWidth: 0 }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } }, tooltip: { callbacks: { label: ctx => `${ctx.label}: ${formatMoney(ctx.raw)}` } } },
            },
        });
    }
    if (yearlyChart.value && props.yearly.length) {
        new Chart(yearlyChart.value, {
            type: 'line',
            data: {
                labels: props.yearly.map(y => y.year),
                datasets: [{
                    data: props.yearly.map(y => y.total),
                    borderColor: props.job.color || '#10b981',
                    backgroundColor: (props.job.color || '#10b981') + '30',
                    tension: 0.3, fill: true, pointRadius: 4,
                }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => formatMoney(ctx.raw) } } },
                scales: { y: { beginAtZero: true, ticks: { callback: v => formatMoney(v) } } },
            },
        });
    }
});
</script>

<template>
    <Head :title="'Trabajo - ' + job.name" />
    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="flex items-center gap-3 text-sm text-gray-500">
                <Link :href="route('jobs.index')" class="hover:text-emerald-600 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    Trabajos
                </Link>
                <span>/</span>
                <span class="text-gray-700 font-medium">{{ job.name }}</span>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div :style="{ backgroundColor: (job.color || '#10b981') + '20' }" class="w-14 h-14 rounded-2xl flex items-center justify-center">
                        <svg :style="{ color: job.color || '#10b981' }" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">{{ job.name }}</h1>
                        <p class="text-sm text-gray-500">
                            <template v-if="stats.first_date">Desde {{ formatDate(stats.first_date) }} · Ultimo: {{ formatDate(stats.last_date) }}</template>
                            <template v-else>Sin ingresos cargados aun</template>
                        </p>
                    </div>
                </div>
                <select :value="year" @change="changeYear($event.target.value)" class="border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 self-start">
                    <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                </select>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-emerald-500 to-cyan-600 rounded-2xl p-5 text-white shadow-lg">
                    <p class="text-emerald-100 text-xs">Total {{ year }}</p>
                    <p class="text-2xl font-bold mt-1">{{ formatMoney(stats.total_year_ars) }}</p>
                    <p class="text-emerald-100 text-xs mt-1">≈ {{ formatMoney(stats.total_year_usd, 'USD') }}</p>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <p class="text-gray-400 text-xs">Total historico</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ formatMoney(stats.total_all_ars) }}</p>
                    <p class="text-gray-400 text-xs mt-1">≈ {{ formatMoney(stats.total_all_usd, 'USD') }}</p>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <p class="text-gray-400 text-xs">Ingresos {{ year }}</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ stats.count_year }}</p>
                    <p class="text-gray-400 text-xs mt-1">{{ stats.count_all }} totales</p>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <p class="text-gray-400 text-xs">Promedio por cobro</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ formatMoney(stats.avg_per_income_ars) }}</p>
                    <p class="text-gray-400 text-xs mt-1">historico</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 lg:col-span-2">
                    <h3 class="text-base font-semibold text-gray-800 mb-4">Evolucion mensual ({{ year }})</h3>
                    <div class="h-72"><canvas ref="monthChart"></canvas></div>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800 mb-4">Por cuenta ({{ year }})</h3>
                    <div v-if="byAccount.length" class="h-72"><canvas ref="accountChart"></canvas></div>
                    <p v-else class="text-sm text-gray-400 text-center py-12">Sin ingresos en {{ year }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800 mb-4">Ingresos por año</h3>
                    <div v-if="yearly.length" class="h-56"><canvas ref="yearlyChart"></canvas></div>
                    <p v-else class="text-sm text-gray-400 text-center py-8">Sin datos</p>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800 mb-4">Desglose ({{ year }})</h3>
                    <div class="space-y-4">
                        <div v-if="byCurrency.length">
                            <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold mb-2">Por moneda</p>
                            <div class="space-y-1">
                                <div v-for="c in byCurrency" :key="c.currency" class="flex justify-between text-sm">
                                    <span class="text-gray-600">{{ c.currency }} <span class="text-xs text-gray-400">({{ c.count }})</span></span>
                                    <span class="font-medium text-gray-800">{{ formatMoney(c.total, c.currency) }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-if="byPerson.length">
                            <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold mb-2">Por persona</p>
                            <div class="space-y-1">
                                <div v-for="p in byPerson" :key="p.user_id" class="flex justify-between text-sm">
                                    <span class="text-gray-600">{{ p.name }} <span class="text-xs text-gray-400">({{ p.count }})</span></span>
                                    <span class="font-medium text-gray-800">{{ formatMoney(p.total) }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-if="byAccount.length">
                            <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold mb-2">Por cuenta</p>
                            <div class="space-y-1">
                                <div v-for="a in byAccount" :key="a.account_id ?? 'none'" class="flex justify-between text-sm">
                                    <span class="text-gray-600 flex items-center gap-2">
                                        <span class="inline-block w-2 h-2 rounded-full" :style="{ backgroundColor: a.account_color }"></span>
                                        {{ a.account_name }} <span class="text-xs text-gray-400">({{ a.count }})</span>
                                    </span>
                                    <span class="font-medium text-gray-800">{{ formatMoney(a.total) }}</span>
                                </div>
                            </div>
                        </div>
                        <p v-if="!byCurrency.length && !byPerson.length && !byAccount.length" class="text-sm text-gray-400 text-center py-6">Sin datos en {{ year }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-800">Ingresos registrados</h3>
                    <Link :href="route('incomes.index')" class="text-xs text-emerald-600 hover:text-emerald-700 font-medium">Cargar nuevo ingreso →</Link>
                </div>
                <div v-if="incomes.length" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                            <tr>
                                <th class="px-6 py-3 text-left">Fecha</th>
                                <th class="px-6 py-3 text-left">Descripcion</th>
                                <th class="px-6 py-3 text-left">Persona</th>
                                <th class="px-6 py-3 text-left">Cuenta</th>
                                <th class="px-6 py-3 text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="i in incomes" :key="i.id" class="border-t border-gray-100 hover:bg-gray-50">
                                <td class="px-6 py-3 text-gray-600">{{ formatDate(i.date) }}</td>
                                <td class="px-6 py-3 text-gray-800">{{ i.description }}<span v-if="i.source" class="text-gray-400 text-xs"> · {{ i.source }}</span></td>
                                <td class="px-6 py-3 text-gray-600">{{ i.user?.name }}</td>
                                <td class="px-6 py-3 text-gray-600">
                                    <span v-if="i.account" class="inline-flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: i.account.color }"></span>
                                        {{ i.account.name }}
                                    </span>
                                    <span v-else class="text-gray-400">Sin cuenta</span>
                                </td>
                                <td class="px-6 py-3 text-right font-semibold text-emerald-600">{{ formatMoney(i.amount, i.currency || 'ARS') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="px-6 py-8 text-center text-sm text-gray-400">Aun no hay ingresos cargados para este trabajo.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
