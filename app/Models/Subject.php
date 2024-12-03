<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;
    protected $primaryKey = 'subject_id';
    protected $fillable = [
        'subject_name',
        'grade_level',
        'strand'
    ];
}
