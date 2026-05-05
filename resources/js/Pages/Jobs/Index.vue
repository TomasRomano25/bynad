<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/UI/Modal.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import { formatMoney, formatDate } from '@/helpers';
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

const props = defineProps({ jobs: Array, usdRate: Number, year: Number });

const showCreateModal = ref(false);
const editing = ref(null);
const form = useForm({ name: '', color: '#10b981' });

const openCreate = () => { editing.value = null; form.reset(); form.color = '#10b981'; showCreateModal.value = true; };
const openEdit = (job) => { editing.value = job; form.name = job.name; form.color = job.color || '#10b981'; showCreateModal.value = true; };
const submit = () => {
    if (editing.value) {
        form.put(route('income-jobs.update', editing.value.id), { onSuccess: () => { showCreateModal.value = false; }, preserveScroll: true });
    } else {
        form.post(route('income-jobs.store'), { onSuccess: () => { showCreateModal.value = false; form.reset(); }, preserveScroll: true });
    }
};
const destroy = (job) => {
    if (!confirm(`Eliminar trabajo "${job.name}"? Los ingresos existentes mantendran el nombre.`)) return;
    useForm({}).delete(route('income-jobs.destroy', job.id), { preserveScroll: true });
};

const totalAllJobsArs = computed(() => props.jobs.reduce((s, j) => s + (j.total_year_ars || 0), 0));
const totalAllJobsUsd = computed(() => props.jobs.reduce((s, j) => s + (j.total_year_usd || 0), 0));

const sortBy = ref('total');
const sortedJobs = computed(() => {
    const list = [...(props.jobs || [])];
    if (sortBy.value === 'name') return list.sort((a, b) => a.name.localeCompare(b.name));
    if (sortBy.value === 'last') return list.sort((a, b) => (b.last_date || '').localeCompare(a.last_date || ''));
    return list.sort((a, b) => (b.total_year_ars || 0) - (a.total_year_ars || 0));
});

const yearOptions = computed(() => {
    const cy = new Date().getFullYear();
    return [cy - 2, cy - 1, cy, cy + 1];
});
const changeYear = (y) => router.get(route('jobs.index'), { year: y }, { preserveState: true, preserveScroll: true });

const sparkRefs = ref({});
const setSparkRef = (id) => (el) => { if (el) sparkRefs.value[id] = el; };

const drawSparks = () => {
    props.jobs.forEach(j => {
        const el = sparkRefs.value[j.id];
        if (!el) return;
        if (el._chart) el._chart.destroy();
        el._chart = new Chart(el, {
            type: 'bar',
            data: { labels: ['E','F','M','A','M','J','J','A','S','O','N','D'], datasets: [{ data: j.monthly, backgroundColor: j.color || '#10b981', borderRadius: 3 }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: true, callbacks: { label: ctx => formatMoney(ctx.raw) } } },
                scales: { x: { display: false }, y: { display: false } },
            },
        });
    });
};

onMounted(() => nextTick(drawSparks));
watch(() => props.jobs, () => nextTick(drawSparks), { deep: true });
</script>

<template>
    <Head title="Trabajos" />
    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Trabajos</h1>
                    <p class="text-sm text-gray-500 mt-1">Administra tus trabajos y analiza ingresos por fuente</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <select :value="year" @change="changeYear($event.target.value)" class="border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500">
                        <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                    </select>
                    <select v-model="sortBy" class="border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500">
                        <option value="total">Mayor ingreso</option>
                        <option value="name">Nombre</option>
                        <option value="last">Ultimo cobro</option>
                    </select>
                    <button @click="openCreate" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-emerald-500 to-cyan-600 text-white text-sm font-medium rounded-xl shadow-lg shadow-emerald-200/50 hover:shadow-xl transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Nuevo Trabajo
                    </button>
                </div>
            </div>

            <div class="bg-gradient-to-r from-emerald-500 to-cyan-600 rounded-2xl p-6 text-white shadow-xl">
                <p class="text-emerald-100 text-sm">Total ingresos {{ year }} (todos los trabajos)</p>
                <p class="text-3xl font-bold mt-1">{{ formatMoney(totalAllJobsArs) }}</p>
                <p class="text-emerald-200 text-sm mt-1">{{ formatMoney(totalAllJobsUsd, 'USD') }}</p>
            </div>

            <div v-if="!jobs.length" class="bg-white rounded-2xl p-12 text-center shadow-sm border border-gray-100">
                <p class="text-gray-500">Aun no tenes trabajos cargados</p>
                <button @click="openCreate" class="mt-4 text-emerald-600 text-sm font-medium hover:text-emerald-700">Agregar primer trabajo</button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="job in sortedJobs" :key="job.id"
                     class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div :style="{ backgroundColor: (job.color || '#10b981') + '20' }" class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0">
                                <svg :style="{ color: job.color || '#10b981' }" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-semibold text-gray-800 truncate">{{ job.name }}</h3>
                                <p class="text-xs text-gray-400">{{ job.count_year }} ingresos en {{ year }} · {{ job.count }} totales</p>
                            </div>
                        </div>
                        <div class="flex gap-1 shrink-0">
                            <button @click="openEdit(job)" class="p-1.5 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            </button>
                            <button @click="destroy(job)" class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <p class="text-2xl font-bold text-gray-800">{{ formatMoney(job.total_year_ars) }}</p>
                        <p class="text-xs text-gray-400">≈ {{ formatMoney(job.total_year_usd, 'USD') }} en {{ year }}</p>
                    </div>

                    <div class="h-12 mb-3"><canvas :ref="setSparkRef(job.id)"></canvas></div>

                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-400">
                            <template v-if="job.last_date">Ultimo: {{ formatDate(job.last_date) }} · {{ formatMoney(job.last_amount, job.last_currency) }}</template>
                            <template v-else>Sin ingresos cargados</template>
                        </span>
                        <Link :href="route('jobs.show', job.id)" class="text-emerald-600 hover:text-emerald-700 font-medium flex items-center gap-1">
                            Ver detalle
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showCreateModal" @close="showCreateModal = false" :title="editing ? 'Editar trabajo' : 'Nuevo trabajo'">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                    <input v-model="form.name" type="text" required maxlength="100" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500" placeholder="Ej: Improntus" />
                    <p v-if="form.errors.name" class="text-rose-500 text-xs mt-1">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                    <input v-model="form.color" type="color" class="w-16 h-10 border border-gray-300 rounded-xl cursor-pointer" />
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showCreateModal = false" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200">Cancelar</button>
                    <button type="submit" :disabled="form.processing" class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-emerald-500 to-cyan-600 rounded-xl shadow-lg shadow-emerald-200/50 disabled:opacity-50">
                        {{ editing ? 'Guardar' : 'Crear' }}
                    </button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
