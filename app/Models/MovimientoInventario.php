<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MovimientoInventario extends Model
{
    use HasFactory;

    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'tipo',
        'item_id',
        'cantidad',
        'unidad_medida',
        'almacen_origen_id',
        'almacen_destino_id',
        'responsable_id',
        'orden_compra_id',
        'solicitud_id',
        'motivo',
        'observaciones',
        'fecha_movimiento',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'fecha_movimiento' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function almacenOrigen()
    {
        return $this->belongsTo(Almacen::class, 'almacen_origen_id');
    }

    public function almacenDestino()
    {
        return $this->belongsTo(Almacen::class, 'almacen_destino_id');
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    // public function ordenCompra()
    // {
    //     return $this->belongsTo(OrdenCompra::class);
    // }

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }

    // Scopes
    public function scopeEntradas($query)
    {
        return $query->where('tipo', 'entrada');
    }

    public function scopeSalidas($query)
    {
        return $query->where('tipo', 'salida');
    }

    public function scopeTraspasos($query)
    {
        return $query->where('tipo', 'traspaso');
    }

    public function scopeAjustes($query)
    {
        return $query->where('tipo', 'ajuste');
    }

    public function scopePorAlmacen($query, $almacenId)
    {
        return $query->where(function ($q) use ($almacenId) {
            $q->where('almacen_origen_id', $almacenId)
              ->orWhere('almacen_destino_id', $almacenId);
        });
    }

    public function scopePorItem($query, $itemId)
    {
        return $query->where('item_id', $itemId);
    }

    public function scopePorFecha($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha_movimiento', [$fechaInicio, $fechaFin]);
    }

    // Métodos estáticos para registrar movimientos
    public static function registrarEntrada($data)
    {
        DB::beginTransaction();
        try {
            // Crear movimiento
            $movimiento = self::create([
                'tipo' => 'entrada',
                'item_id' => $data['item_id'],
                'cantidad' => $data['cantidad'],
                'unidad_medida' => $data['unidad_medida'],
                'almacen_destino_id' => $data['almacen_id'],
                'responsable_id' => $data['responsable_id'],
                'orden_compra_id' => $data['orden_compra_id'] ?? null,
                'motivo' => $data['motivo'],
                'observaciones' => $data['observaciones'] ?? null,
                'fecha_movimiento' => $data['fecha_movimiento'] ?? now(),
            ]);

            // Actualizar inventario
            $inventario = Inventario::firstOrCreate(
                [
                    'almacen_id' => $data['almacen_id'],
                    'item_id' => $data['item_id'],
                ],
                [
                    'cantidad_actual' => 0,
                    'cantidad_reservada' => 0,
                    'stock_minimo' => $data['stock_minimo'] ?? 0,
                    'ultima_actualizacion' => now(),
                ]
            );

            $inventario->incrementar($data['cantidad']);

            DB::commit();
            return $movimiento;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public static function registrarSalida($data)
    {
        DB::beginTransaction();
        try {
            // Verificar stock disponible
            $inventario = Inventario::where('almacen_id', $data['almacen_id'])
                ->where('item_id', $data['item_id'])
                ->first();

            if (!$inventario || $inventario->cantidad_disponible < $data['cantidad']) {
                throw new \Exception('Stock insuficiente');
            }

            // Crear movimiento
            $movimiento = self::create([
                'tipo' => 'salida',
                'item_id' => $data['item_id'],
                'cantidad' => $data['cantidad'],
                'unidad_medida' => $data['unidad_medida'],
                'almacen_origen_id' => $data['almacen_id'],
                'responsable_id' => $data['responsable_id'],
                'solicitud_id' => $data['solicitud_id'] ?? null,
                'motivo' => $data['motivo'],
                'observaciones' => $data['observaciones'] ?? null,
                'fecha_movimiento' => $data['fecha_movimiento'] ?? now(),
            ]);

            // Actualizar inventario
            $inventario->decrementar($data['cantidad']);

            DB::commit();
            return $movimiento;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public static function registrarTraspaso($data)
    {
        DB::beginTransaction();
        try {
            // Verificar stock en origen
            $inventarioOrigen = Inventario::where('almacen_id', $data['almacen_origen_id'])
                ->where('item_id', $data['item_id'])
                ->first();

            if (!$inventarioOrigen || $inventarioOrigen->cantidad_disponible < $data['cantidad']) {
                throw new \Exception('Stock insuficiente en almacén origen');
            }

            // Crear movimiento
            $movimiento = self::create([
                'tipo' => 'traspaso',
                'item_id' => $data['item_id'],
                'cantidad' => $data['cantidad'],
                'unidad_medida' => $data['unidad_medida'],
                'almacen_origen_id' => $data['almacen_origen_id'],
                'almacen_destino_id' => $data['almacen_destino_id'],
                'responsable_id' => $data['responsable_id'],
                'motivo' => $data['motivo'],
                'observaciones' => $data['observaciones'] ?? null,
                'fecha_movimiento' => $data['fecha_movimiento'] ?? now(),
            ]);

            // Decrementar en origen
            $inventarioOrigen->decrementar($data['cantidad']);

            // Incrementar en destino
            $inventarioDestino = Inventario::firstOrCreate(
                [
                    'almacen_id' => $data['almacen_destino_id'],
                    'item_id' => $data['item_id'],
                ],
                [
                    'cantidad_actual' => 0,
                    'cantidad_reservada' => 0,
                    'stock_minimo' => $data['stock_minimo'] ?? 0,
                    'ultima_actualizacion' => now(),
                ]
            );

            $inventarioDestino->incrementar($data['cantidad']);

            DB::commit();
            return $movimiento;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public static function registrarAjuste($data)
    {
        DB::beginTransaction();
        try {
            $inventario = Inventario::where('almacen_id', $data['almacen_id'])
                ->where('item_id', $data['item_id'])
                ->first();

            if (!$inventario) {
                throw new \Exception('No existe inventario para este ítem en el almacén');
            }

            $cantidadAjuste = $data['cantidad_nueva'] - $inventario->cantidad_actual;
            
            // Crear movimiento
            $movimiento = self::create([
                'tipo' => 'ajuste',
                'item_id' => $data['item_id'],
                'cantidad' => abs($cantidadAjuste),
                'unidad_medida' => $data['unidad_medida'],
                'almacen_origen_id' => $cantidadAjuste < 0 ? $data['almacen_id'] : null,
                'almacen_destino_id' => $cantidadAjuste > 0 ? $data['almacen_id'] : null,
                'responsable_id' => $data['responsable_id'],
                'motivo' => $data['motivo'],
                'observaciones' => $data['observaciones'] ?? null,
                'fecha_movimiento' => $data['fecha_movimiento'] ?? now(),
            ]);

            // Actualizar inventario
            $inventario->cantidad_actual = $data['cantidad_nueva'];
            $inventario->ultima_actualizacion = now();
            $inventario->save();

            DB::commit();
            return $movimiento;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
