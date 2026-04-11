<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CreditCardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\FixedExpenseController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupermarketController;
use App\Http\Controllers\VariableExpenseController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Landing pública
Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/blog', [LandingController::class, 'blog'])->name('blog');
Route::get('/blog/{post:slug}', [LandingController::class, 'post'])->name('blog.post');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Family
    Route::post('/family', [FamilyController::class, 'store'])->name('family.store');
    Route::post('/family/join', [FamilyController::class, 'join'])->name('family.join');

    // Accounts
    Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
    Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
    Route::post('/accounts/transfer', [AccountController::class, 'transfer'])->name('accounts.transfer');
    Route::put('/accounts/transfers/{transfer}', [AccountController::class, 'updateTransfer'])->name('accounts.transfers.update');
    Route::delete('/accounts/transfers/{transfer}', [AccountController::class, 'destroyTransfer'])->name('accounts.transfers.destroy');
    Route::get('/accounts/{account}', [AccountController::class, 'show'])->name('accounts.show');
    Route::put('/accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
    Route::delete('/accounts/{account}', [AccountController::class, 'destroy'])->name('accounts.destroy');

    // Credit Cards
    Route::get('/credit-cards', [CreditCardController::class, 'index'])->name('credit-cards.index');
    Route::post('/credit-cards', [CreditCardController::class, 'store'])->name('credit-cards.store');
    Route::get('/credit-cards/{creditCard}', [CreditCardController::class, 'show'])->name('credit-cards.show');
    Route::put('/credit-cards/{creditCard}', [CreditCardController::class, 'update'])->name('credit-cards.update');
    Route::delete('/credit-cards/{creditCard}', [CreditCardController::class, 'destroy'])->name('credit-cards.destroy');
    Route::post('/credit-cards/{creditCard}/expenses', [CreditCardController::class, 'storeExpense'])->name('credit-cards.expenses.store');
    Route::put('/credit-card-expenses/{expense}', [CreditCardController::class, 'updateExpense'])->name('credit-cards.expenses.update');
    Route::delete('/credit-card-expenses/{expense}', [CreditCardController::class, 'destroyExpense'])->name('credit-cards.expenses.destroy');

    // Fixed Expenses
    Route::get('/fixed-expenses', [FixedExpenseController::class, 'index'])->name('fixed-expenses.index');
    Route::post('/fixed-expenses', [FixedExpenseController::class, 'store'])->name('fixed-expenses.store');
    Route::put('/fixed-expenses/{fixedExpense}', [FixedExpenseController::class, 'update'])->name('fixed-expenses.update');
    Route::delete('/fixed-expenses/{fixedExpense}', [FixedExpenseController::class, 'destroy'])->name('fixed-expenses.destroy');
    Route::post('/fixed-expenses/{fixedExpense}/toggle-payment', [FixedExpenseController::class, 'togglePayment'])->name('fixed-expenses.toggle-payment');

    // Variable Expenses
    Route::get('/variable-expenses', [VariableExpenseController::class, 'index'])->name('variable-expenses.index');
    Route::post('/variable-expenses', [VariableExpenseController::class, 'store'])->name('variable-expenses.store');
    Route::put('/variable-expenses/{variableExpense}', [VariableExpenseController::class, 'update'])->name('variable-expenses.update');
    Route::delete('/variable-expenses/{variableExpense}', [VariableExpenseController::class, 'destroy'])->name('variable-expenses.destroy');

    // Budgets
    Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');
    Route::put('/budgets/{budget}', [BudgetController::class, 'update'])->name('budgets.update');
    Route::delete('/budgets/{budget}', [BudgetController::class, 'destroy'])->name('budgets.destroy');

    // Incomes
    Route::get('/incomes', [IncomeController::class, 'index'])->name('incomes.index');
    Route::post('/incomes', [IncomeController::class, 'store'])->name('incomes.store');
    Route::put('/incomes/{income}', [IncomeController::class, 'update'])->name('incomes.update');
    Route::delete('/incomes/{income}', [IncomeController::class, 'destroy'])->name('incomes.destroy');

    // Assets
    Route::get('/assets', [AssetController::class, 'index'])->name('assets.index');
    Route::post('/assets', [AssetController::class, 'store'])->name('assets.store');
    Route::put('/assets/{asset}', [AssetController::class, 'update'])->name('assets.update');
    Route::delete('/assets/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy');

    // Supermarket
    Route::get('/supermarket', [SupermarketController::class, 'index'])->name('supermarket.index');
    Route::post('/supermarket/purchases', [SupermarketController::class, 'storePurchase'])->name('supermarket.purchases.store');
    Route::delete('/supermarket/purchases/{purchase}', [SupermarketController::class, 'destroyPurchase'])->name('supermarket.purchases.destroy');
    Route::post('/supermarket/products', [SupermarketController::class, 'storeProduct'])->name('supermarket.products.store');
    Route::put('/supermarket/products/{product}', [SupermarketController::class, 'updateProduct'])->name('supermarket.products.update');
    Route::delete('/supermarket/products/{product}', [SupermarketController::class, 'destroyProduct'])->name('supermarket.products.destroy');

});

// Admin panel
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/posts');

    // Posts
    Route::get('/posts', [AdminPostController::class, 'index'])->name('posts.index');
    Route::get('/posts/create', [AdminPostController::class, 'create'])->name('posts.create');
    Route::post('/posts', [AdminPostController::class, 'store'])->name('posts.store');
    Route::get('/posts/{post}/edit', [AdminPostController::class, 'edit'])->name('posts.edit');
    Route::put('/posts/{post}', [AdminPostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post}', [AdminPostController::class, 'destroy'])->name('posts.destroy');

    // Menu
    Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
    Route::post('/menu', [MenuController::class, 'store'])->name('menu.store');
    Route::put('/menu/reorder', [MenuController::class, 'reorder'])->name('menu.reorder');
    Route::put('/menu/{menuItem}', [MenuController::class, 'update'])->name('menu.update');
    Route::delete('/menu/{menuItem}', [MenuController::class, 'destroy'])->name('menu.destroy');

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/backup', [SettingController::class, 'backup'])->name('settings.backup');
    Route::get('/settings/backups', [SettingController::class, 'listBackups'])->name('settings.backups');
});

require __DIR__.'/auth.php';
