<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Leads extends Model
{
	protected $table = 'leads';   

	protected $fillable = [
        'created_at',
        'type',
        'name',
        'email',
        'phone',
        'city',
        'message',
        'post_code',
		'address',
		'date_time',
		'cust_type',
		'water_type',
		'interest_in',
		'problem_with_your_water',
		'water_system_issues',
		'assigned_to',
		'status',
		'converted_date',
		'revenue',
		'assigned_date',
		'utm_source',
		'followup_date',
		'converted_by',
		'created_by',
		'hear_about',
		'friend_name',
		'friend_email',
		'friend_phone',
		'friend_city',
		'closed_reason',
		'is_transfered',
		'installation_date',
		'product_id',
		'water_test_date',
		'installation_status',
		'water_test_status',
		'marketing_email_status',
		'next_marketing_email_date',
		'is_duplicate',
	];
	
	protected $casts = [
        'created_at'  => 'date:M d, Y h:i A',
        'updated_at' => 'datetime:M d, Y h:i A',
        'installation_date' => 'datetime:M d, Y',
        'water_test_date' => 'datetime:M d, Y',
    ];

}
