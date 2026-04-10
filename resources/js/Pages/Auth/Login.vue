<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({ canResetPassword: Boolean, status: String });

const form = useForm({ email: '', password: '', remember: false });

const submit = () => {
    form.post(route('login'), { onFinish: () => form.reset('password') });
};
</script>

<template>
    <GuestLayout>
        <Head title="Iniciar sesion" />

        <div class="text-center mb-6">
            <img src="/logo.svg" alt="Bynad Finance" class="w-16 h-16 rounded-2xl mx-auto mb-4 shadow-lg shadow-blue-200/50" />
            <h2 class="text-xl font-bold text-gray-800">Bienvenido</h2>
            <p class="text-sm text-gray-500 mt-1">Ingresa a Bynad Finance</p>
        </div>

        <div v-if="status" class="mb-4 text-sm font-medium text-emerald-600 bg-emerald-50 rounded-xl p-3 text-center">{{ status }}</div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input id="email" type="email" v-model="form.email" required autofocus autocomplete="username"
                    class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="tu@email.com" />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contrasena</label>
                <input id="password" type="password" v-model="form.password" required autocomplete="current-password"
                    class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" v-model="form.remember" class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                    <span class="text-sm text-gray-600">Recordarme</span>
                </label>
                <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                    Olvidaste tu contrasena?
                </Link>
            </div>

            <button type="submit" :disabled="form.processing"
                class="w-full py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-medium rounded-xl shadow-lg shadow-indigo-200/50 hover:shadow-xl transition-all disabled:opacity-50">
                Iniciar sesion
            </button>

            <p class="text-center text-sm text-gray-500">
                No tenes cuenta?
                <Link :href="route('register')" class="text-indigo-600 hover:text-indigo-700 font-medium">Registrate</Link>
            </p>
        </form>
    </GuestLayout>
</template>
