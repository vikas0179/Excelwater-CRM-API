<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountryCheckerModal extends Model{
    protected $table = 'country_checker_log';
	
	protected $fillable = [
        'ip_address',
        'country_name',
        'country_code'
	];
}
