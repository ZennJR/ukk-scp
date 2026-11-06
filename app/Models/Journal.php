<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    protected $table = 'journals';
    protected $guarded = [];
    public $timestamps = false;

    protected $casts = ['filled_at' => 'datetime'];

    public function jadwal()
    {
        return $this->belongsTo(JadwalPelajaran::class, 'schedule_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'journal_id');
    }
}