<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpareParts extends Model
{
        protected $table = 'spare_parts';

        protected $fillable = [
                'part_name',
                'part_number',
                'price',
                'min_alert_qty',
                'opening_stock',
                'desc',
                'image',
                'stock_qty',
        ];

        protected $casts = [
                'created_at'  => 'date:M d, Y h:i A',
                'updated_at' => 'datetime:M d, Y h:i A',
        ];
}
