<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductResources extends Model
{
    protected $table = 'product_resource';
	
	protected $fillable = [
        'product_id',
        'name',
        'filename',
	];
}