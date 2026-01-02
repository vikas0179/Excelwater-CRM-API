<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orders extends Model
{
   protected $table = 'orders';
	
	protected $fillable = [
		'order_no',
		'user_id',
		'pay_status',
		'base_amount',
        'discount_amount',
        'shipping_amount',
        'tax_amount',
		'tax_percent',
		'total_amount',
        'status',
        'instructions',	
        'order_hash',
		'payment_ref_id',
		'billing_id',		
		'shipping_id',
		'paid_by',
		'discount_code',
		'discount_code_id',
		'discount_type',
		'pay_remarks',
		'currency_symbol'
    ];
} 
