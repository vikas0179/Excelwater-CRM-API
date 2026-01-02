<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Products extends Model
{
	
    protected $table = 'products';
	
	protected $fillable = [
        'product_master_id',
		'parent_id',
		'attribute_id',
		'item_id',
		'title',
        'slug',
        'description',
        'sku',
        'image',
        'image_galley',	
        'category',
        'sub_category',
        'has_stock',
        'status',
        'type',
        'features',
        'secifications',
        'regular_price',
        'sale_price',
        'weight',
        'has_product_atributes',
        'product_atributes',
		'attribute_ids',
		'meta_title',
		'meta_description',
		'meta_keywords',
        'sale_price_usd',
        'regular_price_usd',
        'sale_price_inr',
        'regular_price_inr',
        'reorder',
	];
	
}
