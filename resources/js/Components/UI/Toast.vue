<script setup>
import { ref, watch, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const visible = ref(false);
const message = ref('');
const type = ref('success');
let timeout = null;

const flash = computed(() => page.props.flash);

watch(flash, (val) => {
    if (val?.success) {
        show(val.success, 'success');
    } else if (val?.error) {
        show(val.error, 'error');
    }
}, { deep: true, immediate: true });

function show(msg, t) {
    message.value = msg;
    type.value = t;
    visible.value = true;
    clearTimeout(timeout);
    timeout = setTimeout(() => { visible.value = false; }, 4000);
}
</script>

<template>
    <Transition
        enter-from-class="translate-y-4 opacity-0"
        enter-active-class="transition duration-300 ease-out"
        enter-to-class="translate-y-0 opacity-100"
        leave-from-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-to-class="translate-y-4 opacity-0"
    >
        <div v-if="visible" class="fixed bottom-6 right-6 z-50 max-w-sm">
            <div
                :class="[
                    'flex items-center gap-3 px-5 py-4 rounded-xl shadow-lg border',
                    type === 'success'
                        ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
                        : 'bg-red-50 border-red-200 text-red-800'
                ]"
            >
                <svg v-if="type === 'success'" class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <svg v-else class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm font-medium">{{ message }}</p>
                <button @click="visible = false" class="ml-auto text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </Transition>
</template>
