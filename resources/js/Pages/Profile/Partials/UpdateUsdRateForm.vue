<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    usdRate: {
        type: Number,
        default: null,
    },
    familyName: {
        type: String,
        default: null,
    },
});

const form = useForm({
    usd_rate: props.usdRate ?? 1200,
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Cotización del dólar
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Este valor se usa para convertir entre pesos y dólares en toda la
                plataforma. Es compartido por toda tu familia<span v-if="familyName"> ({{ familyName }})</span>:
                cualquier miembro puede cambiarlo y se aplica para todos. Al
                guardarlo, todos los valores en dólares se recalculan
                automáticamente.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.usd-rate.update'), { preserveScroll: true })"
            class="mt-6 space-y-6"
        >
            <div>
                <InputLabel for="usd_rate" value="1 USD equivale a (ARS)" />

                <div class="mt-1 flex items-center gap-2">
                    <span class="text-gray-500">1 USD =</span>
                    <TextInput
                        id="usd_rate"
                        type="number"
                        step="0.01"
                        min="1"
                        class="block w-40"
                        v-model="form.usd_rate"
                        required
                    />
                    <span class="text-gray-500">ARS</span>
                </div>

                <InputError class="mt-2" :message="form.errors.usd_rate" />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Guardar cotización</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600"
                    >
                        Guardado.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
