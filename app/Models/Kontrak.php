<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kontrak extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_parent',
        'kode',
        'nama',
        'jenis',
        'id_tahun',
        'target',
        'jenis_target',
    ];

    /**
     * Parent dari data kontrak.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'id_parent');
    }

    /**
     * Child langsung.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'id_parent')
            ->orderBy('kode');
    }

     /**
     * Child secara recursive.
     *
     * Contoh:
     * Sasaran
     *   -> IKK
     *      -> Sub IKK
     *          -> Sub Sub IKK
     */
    public function childrenRecursive(): HasMany
    {
        return $this->children()
            ->with('childrenRecursive');
    }

    /**
     * Apakah data ini mempunyai child?
     */
    public function hasChildren(): bool
    {
        return $this->childrenRecursive->isNotEmpty();
    }

    public function realisasis()
    {
        return $this->hasMany(Realisasi::class, 'id_kontrak');
    }

    public function realisasiTerakhir()
    {
        return $this->hasOne(Realisasi::class, 'id_kontrak')
            ->latestOfMany('periode');
    }

    public function cascadings()
    {
        return $this->hasMany(
            Cascading::class,
            'id_kontrak'
        );
    }


    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'cascadings',
            'id_kontrak',
            'id_user'
        );
    }
}
