<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    family_code: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Registro" />

        <div class="text-center mb-6">
            <img src="/logo.svg" alt="Bynad Finance" class="w-16 h-16 rounded-2xl mx-auto mb-4 shadow-lg shadow-blue-200/50" />
            <h2 class="text-xl font-bold text-gray-800">Crear cuenta</h2>
            <p class="text-sm text-gray-500 mt-1">Unite a Bynad Finance</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                <input id="name" type="text" v-model="form.name" required autofocus autocomplete="name"
                    class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Tu nombre" />
                <InputError class="mt-1" :message="form.errors.name" />
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input id="email" type="email" v-model="form.email" required autocomplete="username"
                    class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="tu@email.com" />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contrasena</label>
                <input id="password" type="password" v-model="form.password" required autocomplete="new-password"
                    class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmar contrasena</label>
                <input id="password_confirmation" type="password" v-model="form.password_confirmation" required autocomplete="new-password"
                    class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                <InputError class="mt-1" :message="form.errors.password_confirmation" />
            </div>

            <div>
                <label for="family_code" class="block text-sm font-medium text-gray-700 mb-1">Codigo de familia (opcional)</label>
                <input id="family_code" type="text" v-model="form.family_code"
                    class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Si tu pareja ya se registro, pedi su codigo" />
                <p class="text-xs text-gray-400 mt-1">Dejalo vacio para crear una nueva familia</p>
            </div>

            <button type="submit" :disabled="form.processing"
                class="w-full py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-medium rounded-xl shadow-lg shadow-indigo-200/50 hover:shadow-xl transition-all disabled:opacity-50">
                Crear cuenta
            </button>

            <p class="text-center text-sm text-gray-500">
                Ya tenes cuenta?
                <Link :href="route('login')" class="text-indigo-600 hover:text-indigo-700 font-medium">Inicia sesion</Link>
            </p>
        </form>
    </GuestLayout>
</template>
