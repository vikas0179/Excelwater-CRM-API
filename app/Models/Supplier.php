<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
	protected $table = 'supplier';   

	protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'tan_number',
        'logo',
	];
	
	protected $casts = [
        'created_at'  => 'date:M d, Y h:i A',
        'updated_at' => 'datetime:M d, Y h:i A',
    ];

}
