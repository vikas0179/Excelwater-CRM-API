<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductsVariations extends Model
{
	protected $table = 'products_variations';

	protected $fillable = [
        'product_id',
        'sale_price',
        'regular_price',
        'weight',
        'has_stock',
        'sale_price_usd',
        'regular_price_usd',
        'sale_price_inr',
        'regular_price_inr',
    ];
}

