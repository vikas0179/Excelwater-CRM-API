<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadsHistory extends Model
{
	protected $table = 'leads_history';   

	protected $fillable = [
        'lead_id',
        'user_id',
        'assigned_by',
        'message',
        'followup_date',
        'attachment',
        'created_at'
	];
	
	protected $casts = [
        //'created_at'  => 'date:M d, Y h:i A',
        //'updated_at' => 'datetime:M d, Y h:i A',
    ];

}
