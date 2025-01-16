<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;
    protected $primaryKey = 'enrol_id';
    protected $fillable = [
        'LRN', 
        'regapproval_date', 
        'payment_approval', 
        'grade_level', 
        'guardian_name', 
        'guardian_no', 
        'last_attended', 
        'public_private', 
        'date_register', 
        'strand', 
        'school_year',
        'old_account'
    ];
}
