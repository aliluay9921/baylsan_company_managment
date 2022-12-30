<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guarantee extends Model
{
    use HasFactory, Uuids;
    protected $guarded = [];
    public function worker()
    {
        return $this->belongsTo(Worker::class, 'worker_id');
    }
}