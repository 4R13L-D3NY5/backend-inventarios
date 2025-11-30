<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class OrdenCompra extends Model
{
    protected $table = 'ordenes_compra';

    protected $fillable = [
        'numero_orden',
        'solicitud_id',
        'proveedor_id',
        'fecha_emision',
        'fecha_entrega_estimada',
        'fecha_entrega_real',
        'estado',
        'subtotal',
        'impuestos',
        'total',
        'moneda',
        'condiciones_pago',
        'observaciones',
        'usuario_creador_id',
        'usuario_aprobador_id',
        'fecha_aprobacion',
        'activo',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_entrega_estimada' => 'date',
        'fecha_entrega_real' => 'date',
        'fecha_aprobacion' => 'datetime',
        'subtotal' => 'decimal:2',
        'impuestos' => 'decimal:2',
        'total' => 'decimal:2',
        'activo' => 'boolean',
    ];

    // Relaciones
    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class);
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function usuarioCreador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_creador_id');
    }

    public function usuarioAprobador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_aprobador_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrdenCompraItem::class);
    }

    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    // Métodos de negocio

    /**
     * Genera el número de orden automáticamente
     */
    public static function generarNumeroOrden(): string
    {
        $year = date('Y');
        $ultimaOrden = self::where('numero_orden', 'like', "OC-{$year}-%")
                          ->orderBy('numero_orden', 'desc')
                          ->first();

        if ($ultimaOrden) {
            $ultimoNumero = (int) substr($ultimaOrden->numero_orden, -4);
            $nuevoNumero = $ultimoNumero + 1;
        } else {
            $nuevoNumero = 1;
        }

        return sprintf('OC-%s-%04d', $year, $nuevoNumero);
    }

    /**
     * Calcula los totales de la orden basándose en los ítems
     */
    public function calcularTotales(): void
    {
        $this->subtotal = $this->items->sum('subtotal');
        $this->total = $this->subtotal + $this->impuestos;
        $this->save();
    }

    /**
     * Aprueba la orden de compra
     */
    public function aprobar(User $aprobador): bool
    {
        if ($this->estado !== 'borrador') {
            return false;
        }

        if ($this->items->isEmpty()) {
            return false;
        }

        $this->estado = 'enviada';
        $this->usuario_aprobador_id = $aprobador->id;
        $this->fecha_aprobacion = now();
        
        return $this->save();
    }

    /**
     * Confirma la orden (proveedor acepta)
     */
    public function confirmar(): bool
    {
        if ($this->estado !== 'enviada') {
            return false;
        }

        $this->estado = 'confirmada';
        return $this->save();
    }

    /**
     * Registra la recepción de ítems
     */
    public function registrarRecepcion(array $itemsRecibidos, int $almacenId, int $usuarioId): bool
    {
        if (!in_array($this->estado, ['confirmada', 'recibida_parcial'])) {
            return false;
        }

        foreach ($itemsRecibidos as $itemData) {
            $ordenItem = $this->items()->find($itemData['orden_compra_item_id']);
            
            if (!$ordenItem) {
                continue;
            }

            $cantidadRecibir = min(
                $itemData['cantidad'],
                $ordenItem->cantidadPendiente()
            );

            if ($cantidadRecibir <= 0) {
                continue;
            }

            // Actualizar cantidad recibida
            $ordenItem->cantidad_recibida += $cantidadRecibir;
            $ordenItem->save();

            // Crear movimiento de inventario (entrada)
            MovimientoInventario::create([
                'almacen_id' => $almacenId,
                'item_id' => $ordenItem->item_id,
                'tipo_movimiento' => 'entrada',
                'cantidad' => $cantidadRecibir,
                'unidad_medida' => $ordenItem->unidad_medida,
                'orden_compra_id' => $this->id,
                'usuario_id' => $usuarioId,
                'fecha_movimiento' => now(),
                'observaciones' => "Recepción de OC {$this->numero_orden}",
            ]);
        }

        // Actualizar estado de la orden
        if ($this->estaCompletamenteRecibida()) {
            $this->estado = 'recibida_completa';
            $this->fecha_entrega_real = now();
        } else {
            $this->estado = 'recibida_parcial';
        }

        return $this->save();
    }

    /**
     * Cancela la orden de compra
     */
    public function cancelar(string $motivo = null): bool
    {
        if (in_array($this->estado, ['recibida_completa', 'cancelada'])) {
            return false;
        }

        $this->estado = 'cancelada';
        
        if ($motivo) {
            $this->observaciones = ($this->observaciones ? $this->observaciones . "\n\n" : '') 
                                 . "CANCELADA: {$motivo}";
        }

        return $this->save();
    }

    /**
     * Verifica si todos los ítems fueron completamente recibidos
     */
    public function estaCompletamenteRecibida(): bool
    {
        return $this->items->every(function ($item) {
            return $item->estaCompletamenteRecibido();
        });
    }

    // Scopes

    public function scopePorEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopePorProveedor($query, int $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    public function scopePendientesRecepcion($query)
    {
        return $query->whereIn('estado', ['confirmada', 'recibida_parcial']);
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}
