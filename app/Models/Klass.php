<?php

namespace App\Models;

use App\Models\Subject;
use App\Models\Admin;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Klass extends Model
{
    use HasFactory;
    protected $primaryKey = 'class_id';
    protected $fillable = [
        'admin_id',
        'subject_id',
        'section_id',
        'room',
        'schedule',
        'time',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
