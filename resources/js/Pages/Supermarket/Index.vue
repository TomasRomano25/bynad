<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/UI/Modal.vue';
import MonthSelector from '@/Components/UI/MonthSelector.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { formatMoney } from '@/helpers';

const props = defineProps({ purchases: Array, products: Array, accounts: Array, filters: Object, totalMonth: Number, totalMonthUsd: Number, usdRate: Number });
const showPurchaseModal = ref(false);
const showProductModal = ref(false);
const editingProduct = ref(null);
const searchProduct = ref('');
const selectedCategory = ref('all');

const purchaseForm = useForm({ account_id: null, date: new Date().toISOString().split('T')[0], store: '', items: [{ supermarket_product_id: null, custom_name: '', quantity: 1, price: 0, is_necessary: true }] });
const productForm = useForm({ name: '', category: '', default_price: 0, is_necessary: true });

const categories = computed(() => [...new Set(props.products.map(p => p.category))].sort());
const filteredProducts = computed(() => {
    let prods = props.products;
    if (selectedCategory.value !== 'all') prods = prods.filter(p => p.category === selectedCategory.value);
    if (searchProduct.value) prods = prods.filter(p => p.name.toLowerCase().includes(searchProduct.value.toLowerCase()));
    return prods;
});

const addItem = () => { purchaseForm.items.push({ supermarket_product_id: null, custom_name: '', quantity: 1, price: 0, is_necessary: true }); };
const removeItem = (i) => { purchaseForm.items.splice(i, 1); };
const selectProduct = (item, product) => { item.supermarket_product_id = product.id; item.custom_name = product.name; item.price = product.default_price; item.is_necessary = product.is_necessary; };
const purchaseTotal = computed(() => purchaseForm.items.reduce((acc, i) => acc + (i.price * i.quantity), 0));

const submitPurchase = () => { purchaseForm.post(route('supermarket.purchases.store'), { onSuccess: () => { showPurchaseModal.value = false; purchaseForm.reset(); purchaseForm.items = [{ supermarket_product_id: null, custom_name: '', quantity: 1, price: 0, is_necessary: true }]; } }); };
const destroyPurchase = (p) => { if (confirm('Eliminar compra?')) useForm({}).delete(route('supermarket.purchases.destroy', p.id)); };

const openEditProduct = (p) => { editingProduct.value = p; Object.assign(productForm, { name: p.name, category: p.category, default_price: p.default_price, is_necessary: p.is_necessary }); showProductModal.value = true; };
const openCreateProduct = () => { editingProduct.value = null; productForm.reset(); showProductModal.value = true; };
const submitProduct = () => {
    if (editingProduct.value) productForm.put(route('supermarket.products.update', editingProduct.value.id), { onSuccess: () => showProductModal.value = false });
    else productForm.post(route('supermarket.products.store'), { onSuccess: () => { showProductModal.value = false; productForm.reset(); } });
};
const destroyProduct = (p) => { if (confirm('Eliminar producto?')) useForm({}).delete(route('supermarket.products.destroy', p.id)); };
</script>

<template>
    <Head title="Supermercado" />
    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Supermercado</h1>
                    <p class="text-sm text-gray-500 mt-1">Registra tus compras de supermercado</p>
                </div>
                <div class="flex items-center gap-3 flex-wrap">
                    <MonthSelector :month="filters.month" :year="filters.year" route-name="supermarket.index" />
                    <button @click="showPurchaseModal = true" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 text-white text-sm font-medium rounded-xl shadow-lg hover:shadow-xl transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" /></svg>
                        Nueva Compra
                    </button>
                </div>
            </div>

            <div class="bg-gradient-to-r from-amber-500 to-orange-600 rounded-2xl p-6 text-white shadow-xl">
                <p class="text-amber-100 text-sm">Total supermercado del mes</p>
                <p class="text-3xl font-bold mt-1">{{ formatMoney(totalMonth) }}</p>
                <p class="text-amber-200 text-sm mt-1">{{ formatMoney(totalMonthUsd, 'USD') }}</p>
            </div>

            <!-- Purchases -->
            <div class="space-y-4">
                <div v-for="purchase in purchases" :key="purchase.id" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">{{ purchase.store || 'Supermercado' }} - {{ purchase.date?.split('T')[0] }}</p>
                            <p class="text-xs text-gray-400">{{ purchase.user?.name }} | {{ purchase.account?.name || 'Sin cuenta' }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-lg font-bold text-gray-800">{{ formatMoney(purchase.total) }}</span>
                            <button @click="destroyPurchase(purchase)" class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <div v-for="item in purchase.items" :key="item.id" class="flex items-center justify-between px-5 py-2.5">
                            <div class="flex items-center gap-2">
                                <span :class="item.is_necessary ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'" class="w-2 h-2 rounded-full"></span>
                                <span class="text-sm text-gray-700">{{ item.product?.name || item.custom_name }} x{{ item.quantity }}</span>
                            </div>
                            <span class="text-sm font-medium text-gray-700">{{ formatMoney(item.price * item.quantity) }}</span>
                        </div>
                    </div>
                </div>
                <div v-if="!purchases.length" class="bg-white rounded-2xl p-12 text-center shadow-sm border border-gray-100"><p class="text-gray-400">Sin compras este mes</p></div>
            </div>

            <!-- Product catalog -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-gray-800">Catalogo de Productos</h3>
                    <button @click="openCreateProduct" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">+ Nuevo producto</button>
                </div>
                <div class="flex items-center gap-3 mb-4 flex-wrap">
                    <input v-model="searchProduct" type="text" placeholder="Buscar producto..." class="flex-1 min-w-[200px] border border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500" />
                    <select v-model="selectedCategory" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="all">Todas las categorias</option>
                        <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                    </select>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 max-h-96 overflow-y-auto">
                    <div v-for="p in filteredProducts" :key="p.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                        <div>
                            <p class="text-sm font-medium text-gray-700">{{ p.name }}</p>
                            <p class="text-xs text-gray-400">{{ p.category }} | {{ formatMoney(p.default_price) }}</p>
                        </div>
                        <div class="flex items-center gap-1">
                            <span :class="p.is_necessary ? 'text-emerald-500' : 'text-rose-500'" class="text-xs">{{ p.is_necessary ? 'N' : 'I' }}</span>
                            <button @click="openEditProduct(p)" class="p-1 text-gray-400 hover:text-indigo-600"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
                            <button @click="destroyProduct(p)" class="p-1 text-gray-400 hover:text-rose-600"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase Modal -->
        <Modal :show="showPurchaseModal" @close="showPurchaseModal = false" title="Nueva Compra de Supermercado" max-width="2xl">
            <form @submit.prevent="submitPurchase" class="space-y-4">
                <div class="grid grid-cols-3 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label><input v-model="purchaseForm.date" type="date" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" /></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Supermercado</label><input v-model="purchaseForm.store" type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="Ej: Carrefour" /></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Cuenta</label><select v-model="purchaseForm.account_id" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500"><option :value="null">Seleccionar...</option><option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option></select></div>
                </div>

                <div class="space-y-2 max-h-80 overflow-y-auto">
                    <div v-for="(item, idx) in purchaseForm.items" :key="idx" class="flex items-center gap-2 p-3 bg-gray-50 rounded-xl">
                        <div class="flex-1 min-w-0">
                            <select @change="selectProduct(item, products.find(p => p.id == item.supermarket_product_id))" v-model="item.supermarket_product_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                                <option :value="null">Producto personalizado</option>
                                <optgroup v-for="cat in categories" :key="cat" :label="cat">
                                    <option v-for="p in products.filter(pr => pr.category === cat)" :key="p.id" :value="p.id">{{ p.name }} ({{ formatMoney(p.default_price) }})</option>
                                </optgroup>
                            </select>
                            <input v-if="!item.supermarket_product_id" v-model="item.custom_name" type="text" placeholder="Nombre del producto" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm mt-1 focus:ring-2 focus:ring-indigo-500" />
                        </div>
                        <input v-model="item.quantity" type="number" min="1" class="w-16 border border-gray-200 rounded-lg px-2 py-2 text-sm text-center focus:ring-2 focus:ring-indigo-500" />
                        <input v-model="item.price" type="number" step="0.01" class="w-28 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="Precio" />
                        <label class="flex items-center gap-1 cursor-pointer whitespace-nowrap">
                            <input v-model="item.is_necessary" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-indigo-600" />
                            <span class="text-xs text-gray-500">Nec.</span>
                        </label>
                        <button v-if="purchaseForm.items.length > 1" type="button" @click="removeItem(idx)" class="p-1 text-gray-400 hover:text-rose-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                    </div>
                </div>
                <button type="button" @click="addItem" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">+ Agregar producto</button>

                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                    <p class="text-lg font-bold text-gray-800">Total: {{ formatMoney(purchaseTotal) }}</p>
                    <div class="flex gap-3">
                        <button type="button" @click="showPurchaseModal = false" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200">Cancelar</button>
                        <button type="submit" :disabled="purchaseForm.processing" class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-amber-500 to-orange-600 rounded-xl shadow-lg disabled:opacity-50">Guardar Compra</button>
                    </div>
                </div>
            </form>
        </Modal>

        <!-- Product Modal -->
        <Modal :show="showProductModal" @close="showProductModal = false" :title="editingProduct ? 'Editar Producto' : 'Nuevo Producto'">
            <form @submit.prevent="submitProduct" class="space-y-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label><input v-model="productForm.name" type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" /></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label><input v-model="productForm.category" type="text" list="categories" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" /><datalist id="categories"><option v-for="c in categories" :key="c" :value="c" /></datalist></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Precio</label><input v-model="productForm.default_price" type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" /></div>
                </div>
                <label class="flex items-center gap-2"><input v-model="productForm.is_necessary" type="checkbox" class="w-5 h-5 rounded-lg border-gray-300 text-indigo-600" /><span class="text-sm text-gray-700">Producto necesario</span></label>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="showProductModal = false" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200">Cancelar</button>
                    <button type="submit" :disabled="productForm.processing" class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-amber-500 to-orange-600 rounded-xl shadow-lg disabled:opacity-50">{{ editingProduct ? 'Guardar' : 'Crear' }}</button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
