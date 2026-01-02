<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftCard extends Model
{
	protected $table = 'gift_card';

	protected $fillable = [
        'amount',
	];
}