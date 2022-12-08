<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory, Uuids;
    protected $guarded = [];
    protected $with = ['worker', 'employee'];


    public function worker()
    {
        return $this->belongsTo(Worker::class, 'target_id');
    }
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'target_id');
    }
}