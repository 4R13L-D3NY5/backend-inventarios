<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialPrecio extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'historial_precios';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'item_id',
        'proveedor_id',
        'precio',
        'fecha_vigencia',
        'moneda',
        'observaciones',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'precio' => 'decimal:2',
        'fecha_vigencia' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con ítem.
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Relación con proveedor.
     */
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    /**
     * Scope para filtrar por ítem.
     */
    public function scopePorItem($query, $itemId)
    {
        return $query->where('item_id', $itemId);
    }

    /**
     * Scope para filtrar por proveedor.
     */
    public function scopePorProveedor($query, $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    /**
     * Scope para obtener precios vigentes (ordenados por fecha desc).
     */
    public function scopeVigentes($query)
    {
        return $query->orderBy('fecha_vigencia', 'desc');
    }

    /**
     * Obtener el precio actual de un ítem para un proveedor específico.
     */
    public static function precioActual($itemId, $proveedorId)
    {
        return self::where('item_id', $itemId)
            ->where('proveedor_id', $proveedorId)
            ->where('fecha_vigencia', '<=', now())
            ->orderBy('fecha_vigencia', 'desc')
            ->first();
    }
}
