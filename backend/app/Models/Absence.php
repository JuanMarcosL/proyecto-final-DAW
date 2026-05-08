<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absence extends Model
{
    use HasFactory;

    protected $fillable = [
        'resource_id',
        'type',
        'start_date',
        'end_date',
        'reason',
        'status',
        'resolved_by',
        'resolved_at',
    ];

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
