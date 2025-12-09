<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RencanaProker extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_proker',
        'rencana_proker',
        'bulan_mulai',
        'minggu_mulai',
        'bulan_akhir',
        'minggu_akhir',
        'status_rencana'
    ];
}
