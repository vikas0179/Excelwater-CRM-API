<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banners extends Model
{
   protected $table = 'banners';

	protected $fillable = [
        'path',
        'mobile_path',
        'type',
        'call_to_actioin_link',
	];
}
