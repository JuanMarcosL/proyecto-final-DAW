<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id',
        'resource_id',
        'scheduled_start',
        'scheduled_end',
        'status',
        'notes',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }
}
