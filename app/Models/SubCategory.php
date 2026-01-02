<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
   protected $table = 'sub_category';

	protected $fillable = [
        'name',
        'slug',
		'c_id',
        'image',
        'status',
        'reorder',
	];
}
