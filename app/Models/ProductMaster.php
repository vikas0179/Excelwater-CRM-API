<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductMaster extends Model
{
	protected $table = 'product_master';   

	protected $fillable = [
        'product_name',
        'product_code',
        'price',
        'desc',
        'image',
        'spare_parts',
        'min_alert_qty',
	];
	
	protected $casts = [
        'created_at'  => 'date:M d, Y h:i A',
        'updated_at' => 'datetime:M d, Y h:i A',
    ];

}
