<script setup>
defineProps({
    title: String,
    value: [String, Number],
    subtitle: String,
    icon: String,
    color: { type: String, default: 'indigo' },
    trend: { type: Number, default: null },
});

const colorClasses = {
    indigo: 'from-indigo-500 to-indigo-600 shadow-indigo-200',
    emerald: 'from-emerald-500 to-emerald-600 shadow-emerald-200',
    rose: 'from-rose-500 to-rose-600 shadow-rose-200',
    amber: 'from-amber-500 to-amber-600 shadow-amber-200',
    purple: 'from-purple-500 to-purple-600 shadow-purple-200',
    cyan: 'from-cyan-500 to-cyan-600 shadow-cyan-200',
};
</script>

<template>
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-500">{{ title }}</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ value }}</p>
                <p v-if="subtitle" class="text-xs text-gray-400 mt-1">{{ subtitle }}</p>
                <div v-if="trend !== null" class="flex items-center gap-1 mt-2">
                    <svg v-if="trend > 0" class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                    <svg v-else class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                    </svg>
                    <span :class="trend > 0 ? 'text-rose-600' : 'text-emerald-600'" class="text-xs font-medium">
                        {{ Math.abs(trend) }}%
                    </span>
                </div>
            </div>
            <div :class="['bg-gradient-to-br shadow-lg', colorClasses[color]]" class="w-12 h-12 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" :d="icon" />
                </svg>
            </div>
        </div>
    </div>
</template>
