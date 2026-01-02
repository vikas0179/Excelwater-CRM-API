<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Addresses extends Model
{
 	protected $table = 'addresses';

	protected $fillable = [
        'user_id',
        'address',
		'city',
        'state',
        'zip_code',
        'country',
		'is_default'
    ];
}
