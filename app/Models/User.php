<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'mobile',
        'password',
        'visible_pass',
        'billing_address',
        'billing_landmark',
        'billing_city',
        'billing_state',
        'billing_zipcode',
        'shipping_address',
        'shipping_landmark',
        'shipping_city',
        'shipping_state',
        'shipping_zipcode',
        'bcc',
        'cc',
    ];
}
