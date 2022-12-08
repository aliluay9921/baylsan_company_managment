<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Worker extends Model
{
    use HasFactory, Uuids, SoftDeletes;
    protected $guarded = [];
    protected $dates = ["deleted_at"];


    public function imports()
    {
        return $this->hasMany(Import::class, 'worker_id');
    }
    public function logs()
    {
        return $this->hasMany(Log::class, 'target_id');
    }
}