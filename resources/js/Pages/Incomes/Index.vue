<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/UI/Modal.vue';
import MonthSelector from '@/Components/UI/MonthSelector.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { formatMoney } from '@/helpers';
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

const props = defineProps({ incomes: Array, accounts: Array, familyUsers: Array, incomeJobs: Array, monthlyData: Array, bySource: Array, filters: Object, totalMonth: Number, totalMonthUsd: Number, usdRate: Number });
const showModal = ref(false);
const editing = ref(null);
const monthChart = ref(null);
const sourceChart = ref(null);

const showJobsModal = ref(false);
const jobForm = useForm({ name: '' });
const editingJob = ref(null);
const editJobForm = useForm({ name: '' });

const sortBy = ref('registered');
const sortDir = ref('desc');

const sortedIncomes = computed(() => {
    const list = [...(props.incomes || [])];
    const dir = sortDir.value === 'asc' ? 1 : -1;
    const cmp = (a, b) => {
        let va, vb;
        switch (sortBy.value) {
            case 'date':
                va = a.date || ''; vb = b.date || '';
                break;
            case 'account':
                va = (a.account?.name || '').toLowerCase();
                vb = (b.account?.name || '').toLowerCase();
                break;
            case 'job':
                va = (a.job || '').toLowerCase();
                vb = (b.job || '').toLowerCase();
                break;
            case 'registered':
            default:
                va = a.id; vb = b.id;
        }
        if (va < vb) return -1 * dir;
        if (va > vb) return 1 * dir;
        return 0;
    };
    return list.sort(cmp);
});

const setSort = (key) => {
    if (sortBy.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = key;
        sortDir.value = key === 'registered' || key === 'date' ? 'desc' : 'asc';
    }
};
const sortIcon = (key) => {
    if (sortBy.value !== key) return '↕';
    return sortDir.value === 'asc' ? '↑' : '↓';
};

const form = useForm({ user_id: '', description: '', amount: 0, currency: 'ARS', account_id: null, source: '', job: '', date: new Date().toISOString().split('T')[0], is_recurring: false, notes: '' });

const openCreate = () => { editing.value = null; form.reset(); form.user_id = props.familyUsers?.[0]?.id ?? ''; form.date = new Date().toISOString().split('T')[0]; form.currency = 'ARS'; showModal.value = true; };
const openEdit = (e) => {
    editing.value = e;
    Object.assign(form, { user_id: e.user_id, description: e.description, amount: e.amount, currency: e.currency ?? 'ARS', account_id: e.account_id, source: e.source, job: e.job, date: e.date?.split('T')[0], is_recurring: e.is_recurring, notes: e.notes });
    showModal.value = true;
};
const submit = () => {
    if (editing.value) form.put(route('incomes.update', editing.value.id), { onSuccess: () => showModal.value = false });
    else form.post(route('incomes.store'), { onSuccess: () => { showModal.value = false; form.reset(); } });
};
const destroy = (e) => { if (confirm('Eliminar?')) useForm({}).delete(route('incomes.destroy', e.id)); };

const submitJob = () => {
    jobForm.post(route('income-jobs.store'), {
        preserveScroll: true,
        onSuccess: () => jobForm.reset(),
    });
};
const startEditJob = (j) => { editingJob.value = j.id; editJobForm.name = j.name; };
const cancelEditJob = () => { editingJob.value = null; editJobForm.reset(); };
const saveEditJob = (j) => {
    editJobForm.put(route('income-jobs.update', j.id), {
        preserveScroll: true,
        onSuccess: () => { editingJob.value = null; editJobForm.reset(); },
    });
};
const destroyJob = (j) => {
    if (!confirm(`Eliminar trabajo "${j.name}"? Los ingresos existentes mantendran el nombre.`)) return;
    useForm({}).delete(route('income-jobs.destroy', j.id), { preserveScroll: true });
};

const months = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

onMounted(() => {
    if (monthChart.value) {
        new Chart(monthChart.value, {
            type: 'bar',
            data: { labels: months, datasets: [{ label: 'Ingresos', data: props.monthlyData.map(m => m.total), backgroundColor: '#10b981', borderRadius: 6 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
        });
    }
    if (sourceChart.value && props.bySource.length) {
        const colors = ['#6366f1', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#06b6d4'];
        new Chart(sourceChart.value, {
            type: 'pie',
            data: { labels: props.bySource.map(s => s.job || 'Sin especificar'), datasets: [{ data: props.bySource.map(s => s.total), backgroundColor: colors, borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } } } },
        });
    }
});
</script>

<template>
    <Head title="Ingresos" />
    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Ingresos</h1>
                    <p class="text-sm text-gray-500 mt-1">Registra y analiza tus fuentes de ingreso</p>
                </div>
                <div class="flex items-center gap-3 flex-wrap">
                    <MonthSelector :month="filters.month" :year="filters.year" route-name="incomes.index" />
                    <button @click="showJobsModal = true" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-50 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        Trabajos
                    </button>
                    <button @click="openCreate" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-emerald-500 to-cyan-600 text-white text-sm font-medium rounded-xl shadow-lg shadow-emerald-200/50 hover:shadow-xl transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Nuevo Ingreso
                    </button>
                </div>
            </div>

            <div class="bg-gradient-to-r from-emerald-500 to-cyan-600 rounded-2xl p-6 text-white shadow-xl">
                <p class="text-emerald-100 text-sm">Total ingresos del mes</p>
                <p class="text-3xl font-bold mt-1">{{ formatMoney(totalMonth) }}</p>
                <p class="text-emerald-200 text-sm mt-1">{{ formatMoney(totalMonthUsd, 'USD') }}</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800 mb-4">Ingresos por mes ({{ filters.year }})</h3>
                    <div class="h-64"><canvas ref="monthChart"></canvas></div>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800 mb-4">Por fuente de trabajo</h3>
                    <div class="h-64"><canvas ref="sourceChart"></canvas></div>
                    <p v-if="!bySource.length" class="text-sm text-gray-400 text-center py-12">Sin datos</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead><tr class="border-b border-gray-100">
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase">
                                <button @click="setSort('registered')" class="inline-flex items-center gap-1 hover:text-gray-800">
                                    Descripcion <span class="text-gray-400">{{ sortIcon('registered') }}</span>
                                </button>
                            </th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase">
                                <button @click="setSort('date')" class="inline-flex items-center gap-1 hover:text-gray-800">
                                    Fecha <span class="text-gray-400">{{ sortIcon('date') }}</span>
                                </button>
                            </th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Monto</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">
                                <button @click="setSort('job')" class="inline-flex items-center gap-1 hover:text-gray-800">
                                    Trabajo <span class="text-gray-400">{{ sortIcon('job') }}</span>
                                </button>
                            </th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">
                                <button @click="setSort('account')" class="inline-flex items-center gap-1 hover:text-gray-800">
                                    Cuenta <span class="text-gray-400">{{ sortIcon('account') }}</span>
                                </button>
                            </th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Persona</th>
                            <th class="text-right px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Acciones</th>
                        </tr></thead>
                        <tbody>
                            <tr v-for="i in sortedIncomes" :key="i.id" class="border-b border-gray-50 hover:bg-gray-50/50">
                                <td class="px-6 py-4"><p class="text-sm font-medium text-gray-800">{{ i.description }}</p></td>
                                <td class="px-6 py-4"><span class="text-sm text-gray-600">{{ i.date?.split('T')[0] }}</span></td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-bold text-emerald-600">{{ formatMoney(i.amount, i.currency ?? 'ARS') }}</p>
                                    <p class="text-xs text-gray-400">
                                        <template v-if="(i.currency ?? 'ARS') === 'USD'">≈ {{ formatMoney(i.amount * usdRate) }}</template>
                                        <template v-else>≈ {{ formatMoney(i.amount / usdRate, 'USD') }}</template>
                                    </p>
                                </td>
                                <td class="px-6 py-4 hidden sm:table-cell"><span class="text-sm text-gray-600">{{ i.job || '-' }}</span></td>
                                <td class="px-6 py-4 hidden sm:table-cell"><span class="text-sm text-gray-600">{{ i.account?.name || '-' }}</span></td>
                                <td class="px-6 py-4 hidden md:table-cell"><span class="text-sm text-gray-600">{{ i.user?.name }}</span></td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-1">
                                        <button @click="openEdit(i)" class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
                                        <button @click="destroy(i)" class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="!sortedIncomes.length" class="p-12 text-center"><p class="text-gray-400">Sin ingresos este mes</p></div>
            </div>
        </div>

        <Modal :show="showModal" @close="showModal = false" :title="editing ? 'Editar Ingreso' : 'Nuevo Ingreso'">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Titular <span class="text-red-500">*</span></label>
                    <select v-model="form.user_id" required class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="" disabled>Selecciona un miembro</option>
                        <option v-for="u in familyUsers" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                    <p v-if="form.errors.user_id" class="text-rose-500 text-xs mt-1">{{ form.errors.user_id }}</p>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Descripcion</label><input v-model="form.description" type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="Ej: Sueldo Abril" /></div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Moneda</label>
                        <div class="flex rounded-xl overflow-hidden border border-gray-300">
                            <button type="button" @click="form.currency = 'ARS'" :class="form.currency === 'ARS' ? 'bg-emerald-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'" class="flex-1 py-2.5 text-sm font-medium transition-colors">ARS</button>
                            <button type="button" @click="form.currency = 'USD'" :class="form.currency === 'USD' ? 'bg-emerald-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'" class="flex-1 py-2.5 text-sm font-medium transition-colors border-l border-gray-300">USD</button>
                        </div>
                    </div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Monto ({{ form.currency }})</label><input v-model="form.amount" type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" /></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label><input v-model="form.date" type="date" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" /></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Trabajo</label>
                        <select v-model="form.job" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="">Sin especificar</option>
                            <option v-for="j in incomeJobs" :key="j.id" :value="j.name">{{ j.name }}</option>
                        </select>
                        <button type="button" @click="showJobsModal = true" class="text-xs text-indigo-600 hover:text-indigo-700 mt-1">+ Gestionar trabajos</button>
                    </div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Cuenta destino</label><select v-model="form.account_id" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500"><option :value="null">Seleccionar...</option><option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option></select></div>
                </div>
                <div class="flex items-center gap-2"><input v-model="form.is_recurring" type="checkbox" class="w-5 h-5 rounded-lg border-gray-300 text-indigo-600" /><span class="text-sm text-gray-700">Ingreso recurrente</span></div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="showModal = false" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200">Cancelar</button>
                    <button type="submit" :disabled="form.processing" class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-emerald-500 to-cyan-600 rounded-xl shadow-lg disabled:opacity-50">{{ editing ? 'Guardar' : 'Agregar' }}</button>
                </div>
            </form>
        </Modal>

        <Modal :show="showJobsModal" @close="showJobsModal = false" title="Gestionar trabajos">
            <div class="space-y-4">
                <form @submit.prevent="submitJob" class="flex gap-2">
                    <input v-model="jobForm.name" type="text" placeholder="Nombre del trabajo (ej: Freelance)" class="flex-1 border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" />
                    <button type="submit" :disabled="jobForm.processing" class="px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-emerald-500 to-cyan-600 rounded-xl shadow disabled:opacity-50">Agregar</button>
                </form>
                <p v-if="jobForm.errors.name" class="text-rose-500 text-xs -mt-2">{{ jobForm.errors.name }}</p>

                <div class="border border-gray-100 rounded-xl divide-y divide-gray-100 max-h-80 overflow-y-auto">
                    <div v-if="!incomeJobs?.length" class="p-6 text-center text-sm text-gray-400">Aun no hay trabajos cargados</div>
                    <div v-for="j in incomeJobs" :key="j.id" class="flex items-center gap-2 px-4 py-3">
                        <template v-if="editingJob === j.id">
                            <input v-model="editJobForm.name" type="text" class="flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500" @keyup.enter="saveEditJob(j)" />
                            <button @click="saveEditJob(j)" class="px-3 py-1.5 text-xs font-medium text-white bg-emerald-500 rounded-lg hover:bg-emerald-600">Guardar</button>
                            <button @click="cancelEditJob" class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">Cancelar</button>
                        </template>
                        <template v-else>
                            <span class="flex-1 text-sm text-gray-700">{{ j.name }}</span>
                            <button @click="startEditJob(j)" class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
                            <button @click="destroyJob(j)" class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                        </template>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="button" @click="showJobsModal = false" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200">Cerrar</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
