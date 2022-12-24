<?php

namespace App\Models;

use Carbon\Carbon;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    use HasFactory, Uuids, SoftDeletes;
    protected $guarded = [];
    protected $dates = ["deleted_at"];
    protected $appends = ['amount_withdraw_salary'];


    public function getAmountWithdrawSalaryAttribute()
    {
        return Log::where("target_id", $this->id)->where("log_type", 1)->whereBetween("created_at", [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->sum("value");
    }
}