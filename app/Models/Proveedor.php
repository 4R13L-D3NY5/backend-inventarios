<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proveedor extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'proveedores';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'nit',
        'telefono',
        'email',
        'direccion',
        'observaciones',
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
        'deleted_at' => 'datetime',
    ];

    /**
     * Scope para filtrar solo proveedores activos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para búsqueda por nombre.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $termino
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBuscar($query, $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('nombre', 'like', "%{$termino}%")
              ->orWhere('nit', 'like', "%{$termino}%")
              ->orWhere('email', 'like', "%{$termino}%");
        });
    }

    // Relaciones futuras (preparadas para próximos módulos)
    
    /**
     * Relación con historial de precios (a implementar).
     */
    // public function historialPrecios()
    // {
    //     return $this->hasMany(HistorialPrecio::class);
    // }

    /**
     * Relación con compras/órdenes de compra (a implementar).
     */
    // public function compras()
    // {
    //     return $this->hasMany(Compra::class);
    // }
}
