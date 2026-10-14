<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Realisasi extends Model
{
    protected $fillable = [
        'id_kontrak',
        'periode',
        'nilai',
    ];

    protected $casts = [
        'periode' => 'date',
        'nilai' => 'decimal:2',
    ];

    public function kontrak()
    {
        return $this->belongsTo(Kontrak::class, 'id_kontrak');
    }
}
