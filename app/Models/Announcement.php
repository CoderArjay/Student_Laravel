<?php

namespace App\Models;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;
    protected $table = 'announcements'; // Specify table name if different
    protected $primaryKey = 'ancmnt_id';
    protected $fillable = [
        'admin_id',
        'class_id',
        'title',
        'announcement'
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

}
