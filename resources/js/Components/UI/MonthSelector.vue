<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    month: Number,
    year: Number,
    routeName: String,
    extraParams: { type: Object, default: () => ({}) },
});

const months = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
];

const navigate = (month, year) => {
    router.get(route(props.routeName), { ...props.extraParams, month, year }, { preserveState: true });
};

const prev = () => {
    let m = props.month - 1;
    let y = props.year;
    if (m < 1) { m = 12; y--; }
    navigate(m, y);
};

const next = () => {
    let m = props.month + 1;
    let y = props.year;
    if (m > 12) { m = 1; y++; }
    navigate(m, y);
};
</script>

<template>
    <div class="flex items-center gap-3 bg-white rounded-xl px-4 py-2 shadow-sm border border-gray-200">
        <button @click="prev" class="p-1 hover:bg-gray-100 rounded-lg transition-colors">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>
        <span class="text-sm font-semibold text-gray-700 min-w-[140px] text-center">
            {{ months[month - 1] }} {{ year }}
        </span>
        <button @click="next" class="p-1 hover:bg-gray-100 rounded-lg transition-colors">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </div>
</template>
