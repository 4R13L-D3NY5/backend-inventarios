<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ubicacion extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'ubicaciones';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'tipo',
        'laboratorio_id',
        'descripcion',
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
     * Relación con laboratorio (si la ubicación pertenece a uno).
     */
    public function laboratorio()
    {
        return $this->belongsTo(Laboratorio::class);
    }

    /**
     * Relación con ítems que tienen esta ubicación inicial.
     */
    public function items()
    {
        return $this->hasMany(Item::class, 'ubicacion_inicial_id');
    }

    /**
     * Scope para filtrar solo ubicaciones activas.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para filtrar por tipo de ubicación.
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Scope para filtrar por laboratorio.
     */
    public function scopePorLaboratorio($query, $laboratorioId)
    {
        return $query->where('laboratorio_id', $laboratorioId);
    }

    /**
     * Scope para búsqueda por nombre.
     */
    public function scopeBuscar($query, $termino)
    {
        return $query->where('nombre', 'like', "%{$termino}%");
    }
}
