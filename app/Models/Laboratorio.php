<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Laboratorio extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'laboratorios';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion',
        'responsable_id',
        'activo',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con el responsable del laboratorio.
     */
    public function responsable()
    {
        return $this->belongsTo(Personal::class, 'responsable_id');
    }

    /**
     * Relación con ítems destinados a este laboratorio.
     */
    public function items()
    {
        return $this->hasMany(Item::class, 'laboratorio_destino_id');
    }

    /**
     * Relación con ubicaciones dentro del laboratorio.
     */
    public function ubicaciones()
    {
        return $this->hasMany(Ubicacion::class);
    }

    /**
     * Scope para filtrar solo laboratorios activos.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para búsqueda por nombre o código.
     */
    public function scopeBuscar($query, $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('nombre', 'like', "%{$termino}%")
              ->orWhere('codigo', 'like', "%{$termino}%");
        });
    }
}
