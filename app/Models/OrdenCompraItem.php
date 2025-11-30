<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenCompraItem extends Model
{
    protected $table = 'orden_compra_items';

    protected $fillable = [
        'orden_compra_id',
        'item_id',
        'cantidad_solicitada',
        'cantidad_recibida',
        'unidad_medida',
        'precio_unitario',
        'subtotal',
        'observaciones',
    ];

    protected $casts = [
        'cantidad_solicitada' => 'decimal:2',
        'cantidad_recibida' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    // Relaciones

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    // Métodos de negocio

    /**
     * Calcula el subtotal del ítem
     */
    public function calcularSubtotal(): float
    {
        return $this->cantidad_solicitada * $this->precio_unitario;
    }

    /**
     * Retorna la cantidad pendiente de recibir
     */
    public function cantidadPendiente(): float
    {
        return max(0, $this->cantidad_solicitada - $this->cantidad_recibida);
    }

    /**
     * Verifica si el ítem está completamente recibido
     */
    public function estaCompletamenteRecibido(): bool
    {
        return $this->cantidad_recibida >= $this->cantidad_solicitada;
    }

    /**
     * Calcula el porcentaje de recepción
     */
    public function porcentajeRecepcion(): float
    {
        if ($this->cantidad_solicitada == 0) {
            return 0;
        }

        return min(100, ($this->cantidad_recibida / $this->cantidad_solicitada) * 100);
    }

    // Event listeners

    protected static function boot()
    {
        parent::boot();

        // Calcular subtotal automáticamente antes de guardar
        static::saving(function ($item) {
            $item->subtotal = $item->calcularSubtotal();
        });
    }
}
