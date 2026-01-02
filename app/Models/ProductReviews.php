<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductReviews extends Model
{
	protected $table = 'product_reviews';
	
	protected $fillable = [
		'product_id',
		'user_id',
		'title',
		'name',
		'email',
		'rating',
		'description',
		'status',
	];
}
