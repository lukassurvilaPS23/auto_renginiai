<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenginioKomentaras extends Model
{
    use HasFactory;

    protected $table = 'renginio_komentarai';

    protected $fillable = [
        'auto_renginys_id',
        'vartotojas_id',
        'komentaras',
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
