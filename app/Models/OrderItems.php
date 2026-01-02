<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItems extends Model
{
    protected $table = 'order_items';
	
	protected $fillable = [
		'order_id',
		'product_id',
		'parent_id',
		'product_name',
        'base_amount',
        'quantity',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'variations',
        'sub_title',
    ];
} 
