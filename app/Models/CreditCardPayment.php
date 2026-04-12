<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditCardPayment extends Model
{
    protected $fillable = ['credit_card_id', 'account_id', 'month', 'year', 'amount', 'currency', 'paid_at'];

    protected $casts = ['amount' => 'decimal:2', 'paid_at' => 'datetime'];

    public function creditCard(): BelongsTo { return $this->belongsTo(CreditCard::class); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
}
