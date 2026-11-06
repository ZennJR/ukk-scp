<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Jurusan extends Model {
    protected $table = 'jurusan';
    protected $primaryKey = 'id_jurusan';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];
}
