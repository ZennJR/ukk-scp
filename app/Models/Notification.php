<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';
    protected $guarded = [];
    public $timestamps = false; // hanya pakai created_at manual (default CURRENT_TIMESTAMP)

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
