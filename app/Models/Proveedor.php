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

    /**
     * Los atributos que se agregan a la serialización del modelo.
     *
     * @var array
     */
    protected $appends = ['items_suministrados'];

    /**
     * Relación con historial de precios.
     */
    public function historialPrecios()
    {
        return $this->hasMany(HistorialPrecio::class);
    }

    /**
     * Accessor para obtener los ítems suministrados como string.
     */
    /**
     * Accessor para obtener los ítems suministrados como string.
     */
    public function getItemsSuministradosAttribute()
    {
        $items = collect();

        // 1. Intentar obtener de historial de precios
        if ($this->relationLoaded('historialPrecios')) {
            $items = $items->concat($this->historialPrecios->map(function ($historial) {
                return $historial->item ? $historial->item->nombre : null;
            }));
        }

        // 2. Intentar obtener de órdenes de compra
        if ($this->relationLoaded('compras')) {
            $this->compras->each(function ($compra) use (&$items) {
                if ($compra->relationLoaded('items')) {
                    $compraItems = $compra->items->map(function ($ordenItem) {
                        return $ordenItem->item ? $ordenItem->item->nombre : null;
                    });
                    $items = $items->concat($compraItems);
                }
            });
        }

        $items = $items->filter()->unique()->values();

        if ($items->isEmpty()) {
            return 'Sin ítems registrados';
        }

        $count = $items->count();
        $limit = 3;

        if ($count <= $limit) {
            return $items->implode(', ');
        }

        return $items->take($limit)->implode(', ') . ' y ' . ($count - $limit) . ' más';
    }

    /**
     * Relación con compras/órdenes de compra.
     */
    public function compras()
    {
        return $this->hasMany(OrdenCompra::class);
    }
}
