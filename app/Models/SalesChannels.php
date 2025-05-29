<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesChannels extends Model
{
	protected $table = 'sales_channels';   

	protected $fillable = [
        'name',
	];
	
	protected $casts = [
        'created_at'  => 'date:M d, Y h:i A',
        'updated_at' => 'datetime:M d, Y h:i A',
    ];

}
