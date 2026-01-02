<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ProductAttributes extends Model{
	protected $table = 'product_attributes';
	
	protected $fillable = [
		'name',
		'slug',
		'description',
		'type',
		'parent_id',
		'is_color',
		'color_code',
		'sort_order',
	];
}