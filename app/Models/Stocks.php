<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stocks extends Model
{
	protected $table = 'stocks';   

	protected $fillable = [
        'order_id',
        'supplier_id',
        'spare_id',
        'qty',
        'price',
        'delivery_date',
        'total_amount',
	];
	
	protected $casts = [
        'created_at'  => 'date:M d, Y h:i A',
        'updated_at' => 'datetime:M d, Y h:i A',
    ];

}
