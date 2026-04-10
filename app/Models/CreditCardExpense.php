<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditCardExpense extends Model
{
    protected $fillable = [
        'credit_card_id', 'user_id', 'description', 'amount', 'amount_usd',
        'total_installments', 'current_installment', 'installment_amount',
        'purchase_date', 'category',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_usd' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'purchase_date' => 'date',
    ];

    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
