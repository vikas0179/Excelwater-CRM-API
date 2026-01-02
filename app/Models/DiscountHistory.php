<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountHistory extends Model
{
   protected $table = 'discount_history';
	
	protected $fillable = [
		'code',
		'order_id',
		'code_id',
		'type',
        'amount',
        'remarks',
        'user_id'
    ];
} 
