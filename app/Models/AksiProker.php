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
        'progress',
        'tgl_pengerjaan'
    ];

    
    public function realisasis()
    {
        return $this->belongsToMany(
            \App\Models\Realisasi::class,
            'realisasi_aksi_proker',
            'aksi_proker_id',
            'realisasi_id'
        )->withTimestamps();
    }

}
