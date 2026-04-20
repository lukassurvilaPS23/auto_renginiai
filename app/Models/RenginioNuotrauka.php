<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenginioNuotrauka extends Model
{
    use HasFactory;

    protected $table = 'renginio_nuotraukos';

    protected $fillable = [
        'auto_renginys_id',
        'vartotojas_id',
        'kelias',
        'statusas',
        'patvirtinta_at',
    ];

    protected $casts = [
        'patvirtinta_at' => 'datetime',
    ];

    public function renginys()
    {
        return $this->belongsTo(AutoRenginys::class, 'auto_renginys_id');
    }

    public function vartotojas()
    {
        return $this->belongsTo(User::class, 'vartotojas_id');
    }
}
