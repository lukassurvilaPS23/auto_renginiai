<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenginioRegistracija extends Model
{
    use HasFactory;

    protected $table = 'renginiu_registracijos';

    protected $fillable = [
        'auto_renginys_id',
        'vartotojas_id',
        'statusas',
        'vardas_pavarde',
        'telefonas',
        'automobilis',
        'valstybinis_nr',
        'komentaras',
        'nuotraukos',
    ];

    protected $casts = [
        'nuotraukos' => 'array',
    ];

    public function autoRenginys()
    {
        return $this->belongsTo(AutoRenginys::class, 'auto_renginys_id');
    }

    public function vartotojas()
{
    return $this->belongsTo(\App\Models\User::class, 'vartotojas_id');
}


}
