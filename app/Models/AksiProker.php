<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AksiProker extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_rencana_proker',
        'kegiatan_proker',
        'bukti_kegiatan',
        'progress'
    ];
}
