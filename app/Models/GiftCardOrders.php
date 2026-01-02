<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftCardOrders extends Model
{
	protected $table = 'gift_card_orders';

	protected $fillable = [
        'user_id',
        'user_email',
        'amount',
        'gift_card_number',
        'to_name',
        'to_email',
        'to_phone',
        'message',
        'paid_by',
        'pay_status',
        'is_claimed',
        'expiry_date',
        'pending_amount',
	];
}