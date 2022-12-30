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
    protected $appends = ["cashing"];


    public function imports()
    {
        return $this->hasMany(Import::class, 'worker_id');
    }
    public function logs()
    {
        return $this->hasMany(Log::class, 'target_id');
    }

    public function getCashingAttribute()
    {
        return Log::where("target_id", $this->id)->sum("value");
    }
}