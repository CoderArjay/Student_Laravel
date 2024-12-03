<?php

namespace App\Models;

use App\Models\Student;
use App\Models\Klass;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;
    protected $primaryKey = 'attendance_id';
    protected $fillable = [
        'LRN',
        'class_id',
        'date',
        'status'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'LRN');
    }

    public function class()
    {
        return $this->belongsTo(Klass::class, 'class_id');
    }
}
