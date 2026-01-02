<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductsVariationsItems extends Model
{
	protected $table = 'products_variations_items';

	protected $fillable = [
        'product_id',
        'attribute_id',
        'item_id',
        'pv_id',
    ];
}

