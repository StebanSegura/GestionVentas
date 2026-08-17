<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    protected $fillable = [
        'import_id', 'order_id', 'order_date', 'customer_id', 'customer_name',
        'product_id', 'product_name', 'category', 'quantity', 'unit_price',
        'discount', 'total', 'country',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}
