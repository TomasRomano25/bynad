<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supermarket_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->decimal('default_price', 15, 2)->default(0);
            $table->decimal('default_price_usd', 15, 2)->default(0);
            $table->boolean('is_necessary')->default(true);
            $table->timestamps();
        });

        Schema::create('supermarket_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->string('store')->nullable();
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('total_usd', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('supermarket_purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supermarket_purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supermarket_product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('custom_name')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('price', 15, 2);
            $table->decimal('price_usd', 15, 2)->default(0);
            $table->boolean('is_necessary')->default(true);
            $table->timestamps();
        });

        // Seed typical Argentine supermarket products
        $products = [
            // Lácteos
            ['name' => 'Leche entera 1L', 'category' => 'Lácteos', 'default_price' => 1200, 'is_necessary' => true],
            ['name' => 'Leche descremada 1L', 'category' => 'Lácteos', 'default_price' => 1300, 'is_necessary' => true],
            ['name' => 'Yogur natural', 'category' => 'Lácteos', 'default_price' => 900, 'is_necessary' => true],
            ['name' => 'Queso cremoso 1kg', 'category' => 'Lácteos', 'default_price' => 5500, 'is_necessary' => true],
            ['name' => 'Queso rallado', 'category' => 'Lácteos', 'default_price' => 2800, 'is_necessary' => true],
            ['name' => 'Manteca 200g', 'category' => 'Lácteos', 'default_price' => 2200, 'is_necessary' => true],
            ['name' => 'Crema de leche', 'category' => 'Lácteos', 'default_price' => 1800, 'is_necessary' => true],
            ['name' => 'Dulce de leche 400g', 'category' => 'Lácteos', 'default_price' => 2500, 'is_necessary' => false],

            // Carnes
            ['name' => 'Asado de tira 1kg', 'category' => 'Carnes', 'default_price' => 8500, 'is_necessary' => true],
            ['name' => 'Vacío 1kg', 'category' => 'Carnes', 'default_price' => 9000, 'is_necessary' => true],
            ['name' => 'Pollo entero', 'category' => 'Carnes', 'default_price' => 4500, 'is_necessary' => true],
            ['name' => 'Pechuga de pollo 1kg', 'category' => 'Carnes', 'default_price' => 5500, 'is_necessary' => true],
            ['name' => 'Carne picada 1kg', 'category' => 'Carnes', 'default_price' => 6000, 'is_necessary' => true],
            ['name' => 'Milanesas 1kg', 'category' => 'Carnes', 'default_price' => 7000, 'is_necessary' => true],
            ['name' => 'Nalga 1kg', 'category' => 'Carnes', 'default_price' => 8000, 'is_necessary' => true],
            ['name' => 'Chorizo parrillero', 'category' => 'Carnes', 'default_price' => 4000, 'is_necessary' => false],

            // Panadería
            ['name' => 'Pan francés 1kg', 'category' => 'Panadería', 'default_price' => 2000, 'is_necessary' => true],
            ['name' => 'Pan lactal', 'category' => 'Panadería', 'default_price' => 2500, 'is_necessary' => true],
            ['name' => 'Facturas x12', 'category' => 'Panadería', 'default_price' => 4500, 'is_necessary' => false],
            ['name' => 'Medialunas x6', 'category' => 'Panadería', 'default_price' => 3000, 'is_necessary' => false],
            ['name' => 'Tapas de empanadas x12', 'category' => 'Panadería', 'default_price' => 1800, 'is_necessary' => true],

            // Almacén
            ['name' => 'Arroz 1kg', 'category' => 'Almacén', 'default_price' => 1500, 'is_necessary' => true],
            ['name' => 'Fideos 500g', 'category' => 'Almacén', 'default_price' => 1200, 'is_necessary' => true],
            ['name' => 'Harina 1kg', 'category' => 'Almacén', 'default_price' => 800, 'is_necessary' => true],
            ['name' => 'Azúcar 1kg', 'category' => 'Almacén', 'default_price' => 1000, 'is_necessary' => true],
            ['name' => 'Aceite girasol 1.5L', 'category' => 'Almacén', 'default_price' => 2500, 'is_necessary' => true],
            ['name' => 'Aceite oliva 500ml', 'category' => 'Almacén', 'default_price' => 5000, 'is_necessary' => false],
            ['name' => 'Sal fina 500g', 'category' => 'Almacén', 'default_price' => 600, 'is_necessary' => true],
            ['name' => 'Yerba mate 1kg', 'category' => 'Almacén', 'default_price' => 3500, 'is_necessary' => true],
            ['name' => 'Café torrado 500g', 'category' => 'Almacén', 'default_price' => 4000, 'is_necessary' => true],
            ['name' => 'Té x25 saquitos', 'category' => 'Almacén', 'default_price' => 1200, 'is_necessary' => true],
            ['name' => 'Galletitas dulces', 'category' => 'Almacén', 'default_price' => 1500, 'is_necessary' => false],
            ['name' => 'Galletitas saladas', 'category' => 'Almacén', 'default_price' => 1200, 'is_necessary' => true],
            ['name' => 'Mermelada 454g', 'category' => 'Almacén', 'default_price' => 2500, 'is_necessary' => false],
            ['name' => 'Atún en lata', 'category' => 'Almacén', 'default_price' => 2000, 'is_necessary' => true],
            ['name' => 'Tomate triturado 520g', 'category' => 'Almacén', 'default_price' => 900, 'is_necessary' => true],
            ['name' => 'Lentejas 500g', 'category' => 'Almacén', 'default_price' => 1200, 'is_necessary' => true],

            // Frutas y Verduras
            ['name' => 'Papa 1kg', 'category' => 'Frutas y Verduras', 'default_price' => 1200, 'is_necessary' => true],
            ['name' => 'Cebolla 1kg', 'category' => 'Frutas y Verduras', 'default_price' => 1000, 'is_necessary' => true],
            ['name' => 'Tomate 1kg', 'category' => 'Frutas y Verduras', 'default_price' => 2500, 'is_necessary' => true],
            ['name' => 'Lechuga', 'category' => 'Frutas y Verduras', 'default_price' => 1500, 'is_necessary' => true],
            ['name' => 'Banana 1kg', 'category' => 'Frutas y Verduras', 'default_price' => 2000, 'is_necessary' => true],
            ['name' => 'Manzana 1kg', 'category' => 'Frutas y Verduras', 'default_price' => 2500, 'is_necessary' => true],
            ['name' => 'Naranja 1kg', 'category' => 'Frutas y Verduras', 'default_price' => 1800, 'is_necessary' => true],
            ['name' => 'Zanahoria 1kg', 'category' => 'Frutas y Verduras', 'default_price' => 1200, 'is_necessary' => true],
            ['name' => 'Zapallo 1kg', 'category' => 'Frutas y Verduras', 'default_price' => 800, 'is_necessary' => true],

            // Bebidas
            ['name' => 'Agua mineral 2L', 'category' => 'Bebidas', 'default_price' => 900, 'is_necessary' => true],
            ['name' => 'Gaseosa cola 2.25L', 'category' => 'Bebidas', 'default_price' => 2500, 'is_necessary' => false],
            ['name' => 'Jugo de naranja 1L', 'category' => 'Bebidas', 'default_price' => 2000, 'is_necessary' => false],
            ['name' => 'Soda 2L', 'category' => 'Bebidas', 'default_price' => 800, 'is_necessary' => true],
            ['name' => 'Cerveza 1L', 'category' => 'Bebidas', 'default_price' => 2500, 'is_necessary' => false],
            ['name' => 'Vino tinto', 'category' => 'Bebidas', 'default_price' => 4000, 'is_necessary' => false],
            ['name' => 'Fernet 750ml', 'category' => 'Bebidas', 'default_price' => 7000, 'is_necessary' => false],

            // Limpieza
            ['name' => 'Detergente 750ml', 'category' => 'Limpieza', 'default_price' => 1500, 'is_necessary' => true],
            ['name' => 'Lavandina 1L', 'category' => 'Limpieza', 'default_price' => 800, 'is_necessary' => true],
            ['name' => 'Jabón en polvo 800g', 'category' => 'Limpieza', 'default_price' => 3500, 'is_necessary' => true],
            ['name' => 'Suavizante 900ml', 'category' => 'Limpieza', 'default_price' => 2500, 'is_necessary' => true],
            ['name' => 'Papel higiénico x4', 'category' => 'Limpieza', 'default_price' => 2800, 'is_necessary' => true],
            ['name' => 'Servilletas x100', 'category' => 'Limpieza', 'default_price' => 1500, 'is_necessary' => true],
            ['name' => 'Esponja', 'category' => 'Limpieza', 'default_price' => 600, 'is_necessary' => true],

            // Congelados
            ['name' => 'Hamburguesas x4', 'category' => 'Congelados', 'default_price' => 3500, 'is_necessary' => false],
            ['name' => 'Papas fritas congeladas 1kg', 'category' => 'Congelados', 'default_price' => 3000, 'is_necessary' => false],
            ['name' => 'Helado 1kg', 'category' => 'Congelados', 'default_price' => 6000, 'is_necessary' => false],

            // Higiene Personal
            ['name' => 'Shampoo 400ml', 'category' => 'Higiene', 'default_price' => 3500, 'is_necessary' => true],
            ['name' => 'Jabón tocador x3', 'category' => 'Higiene', 'default_price' => 1800, 'is_necessary' => true],
            ['name' => 'Pasta dental', 'category' => 'Higiene', 'default_price' => 2200, 'is_necessary' => true],
            ['name' => 'Desodorante', 'category' => 'Higiene', 'default_price' => 3000, 'is_necessary' => true],
        ];

        $now = now();
        foreach ($products as $product) {
            DB::table('supermarket_products')->insert([
                'name' => $product['name'],
                'category' => $product['category'],
                'default_price' => $product['default_price'],
                'default_price_usd' => round($product['default_price'] / 1200, 2),
                'is_necessary' => $product['is_necessary'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supermarket_purchase_items');
        Schema::dropIfExists('supermarket_purchases');
        Schema::dropIfExists('supermarket_products');
    }
};
