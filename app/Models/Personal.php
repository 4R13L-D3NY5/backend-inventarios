<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Personal extends Model
{
    use HasFactory;

    protected $table = 'personals';

    protected $fillable = [
        'nombres',
        'apellidos',
        'ci',
        'celular',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'personal_id');
    }
}
