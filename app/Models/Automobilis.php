<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Automobilis extends Model
{
    use HasFactory;

    protected $table = 'automobiliai';

    protected $fillable = [
        'user_id',
        'marke',
        'modelis',
        'metai',
        'spalva',
        'vin',
        'variklis',
        'kuras',
        'aprasymas',
        'nuotrauka',
    ];

    protected $casts = [
        'metai' => 'integer',
    ];

    public function vartotojas()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
