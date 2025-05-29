<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $table = 'invoice_item';

    protected $fillable = [
        'invoice_id',
        'item',
        'desc',
        'qty',
        'rate',
        'amount',
    ];

    protected $casts = [
        'created_at'  => 'date:M d, Y h:i A',
        'updated_at' => 'datetime:M d, Y h:i A',
    ];
}
