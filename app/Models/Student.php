<?php

namespace App\Models;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Student as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Student extends Model
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $primaryKey = 'LRN';
    public $incrementing = false;
    protected $fillable = [
        'LRN',
        'lname',
        'fname', 
        'mname', 
        'suffix', 
        'bdate', 
        'bplace', 
        'gender', 
        'religion', 
        'address',
        'contact_no',
        'grade_level',
        'email', 
        'password',
        'student_pic'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function enrollment()
    {
        return $this->hasOne(Enrollment::class, 'LRN', 'LRN'); 
    }
}
