<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomeJob extends Model
{
    protected $fillable = ['family_id', 'name', 'color'];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }
}
