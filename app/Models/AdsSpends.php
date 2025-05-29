<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdsSpends extends Model
{
	protected $table = 'ads_spends';   

	protected $fillable = [
        'date',
        'amount',
	];
	
}
