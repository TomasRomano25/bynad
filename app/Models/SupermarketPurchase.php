<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupermarketPurchase extends Model
{
    protected $fillable = [
        'user_id', 'account_id', 'date', 'store', 'total', 'total_usd',
    ];

    protected $casts = [
        'date' => 'date',
        'total' => 'decimal:2',
        'total_usd' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupermarketPurchaseItem::class);
    }

    public function recalculateTotal(): void
    {
        $this->total = $this->items()->sum(\DB::raw('price * quantity'));
        $usdRate = Setting::getUsdRate();
        $this->total_usd = round($this->total / $usdRate, 2);
        $this->save();
    }
}
