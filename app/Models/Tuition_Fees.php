<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tuition_Fees extends Model
{
    use HasFactory;
    protected $table = 'tuition_fees';
    protected $primaryKey = 'fee_id';
    protected $fillable = [
        'grade_level',
        'tuition',
        'general',
        'esc',
        'subsidy',
        'req_downpayment',
    ];
}
