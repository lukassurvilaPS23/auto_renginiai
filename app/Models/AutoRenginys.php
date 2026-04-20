<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutoRenginys extends Model
{
    use HasFactory;

    protected $table = 'auto_renginiai';

    protected $fillable = [
        'pavadinimas',
        'aprasymas',
        'pradzios_data',
        'pabaigos_data',
        'miestas',
        'adresas',
        'latitude',
        'longitude',
        'zemelapio_objektai',
        'statusas',
        'organizatorius_id',
    ];

    protected $casts = [
        'pradzios_data' => 'datetime',
        'pabaigos_data' => 'datetime',
        'zemelapio_objektai' => 'array',
    ];

    public function organizatorius()
    {
        return $this->belongsTo(User::class, 'organizatorius_id');
    }

    public function registracijos()
{
    return $this->hasMany(\App\Models\RenginioRegistracija::class, 'auto_renginys_id');
}

    public function komentarai()
    {
        return $this->hasMany(\App\Models\RenginioKomentaras::class, 'auto_renginys_id');
    }

    public function nuotraukos()
    {
        return $this->hasMany(\App\Models\RenginioNuotrauka::class, 'auto_renginys_id');
    }

}
