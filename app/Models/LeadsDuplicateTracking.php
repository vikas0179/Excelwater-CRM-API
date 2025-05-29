<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadsDuplicateTracking extends Model
{
	protected $table = 'leads_duplicate_tracker';   

	protected $fillable = [
        'lead_id',
        'duplicate_lead_id',
	];
	
	protected $casts = [
        //'created_at'  => 'date:M d, Y h:i A',
        //'updated_at' => 'datetime:M d, Y h:i A',
    ];

}
