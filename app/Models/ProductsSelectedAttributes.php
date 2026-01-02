<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductsSelectedAttributes extends Model
{
	protected $table = 'product_selected_attributes';

	protected $fillable = [
        'product_id',
        'attribute_id',
        'item_id',
    ];
}

