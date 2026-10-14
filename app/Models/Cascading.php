<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cascading extends Model
{

    protected $fillable = [
        'id_kontrak',
        'id_user'
    ];


    public function kontrak()
    {
        return $this->belongsTo(
            Kontrak::class,
            'id_kontrak'
        );
    }


    public function user()
    {
        return $this->belongsTo(
            User::class,
            'id_user'
        );
    }

}