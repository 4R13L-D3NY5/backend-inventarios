<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Prestamo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'prestamos';

    protected $fillable = [
        'codigo',
        'item_id',
        'custodio_id',
        'autorizado_por',
        'almacen_id',
        'laboratorio_id',
        'fecha_prestamo',
        'fecha_vencimiento',
        'fecha_devolucion_real',
        'estado',
        'estado_equipo_prestamo',
        'estado_equipo_devolucion',
        'observaciones_prestamo',
        'observaciones_devolucion',
        'recibido_por',
        'recordatorio_enviado',
        'fecha_recordatorio',
    ];

    protected $casts = [
        'fecha_prestamo' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_devolucion_real' => 'datetime',
        'fecha_recordatorio' => 'datetime',
        'recordatorio_enviado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relaciones
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function custodio()
    {
        return $this->belongsTo(Personal::class, 'custodio_id');
    }

    public function autorizadoPor()
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }

    public function recibidoPor()
    {
        return $this->belongsTo(User::class, 'recibido_por');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }

    public function laboratorio()
    {
        return $this->belongsTo(Laboratorio::class);
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopeVencidos($query)
    {
        return $query->where('estado', 'vencido');
    }

    public function scopeDevueltos($query)
    {
        return $query->where('estado', 'devuelto');
    }

    public function scopePorCustodio($query, $custodioId)
    {
        return $query->where('custodio_id', $custodioId);
    }

    public function scopePorItem($query, $itemId)
    {
        return $query->where('item_id', $itemId);
    }

    public function scopeVencenHoy($query)
    {
        return $query->where('estado', 'activo')
            ->whereDate('fecha_vencimiento', today());
    }

    public function scopeVencenEn($query, $dias)
    {
        return $query->where('estado', 'activo')
            ->whereDate('fecha_vencimiento', today()->addDays($dias));
    }

    // Accessors
    public function getDiasVencidosAttribute()
    {
        if ($this->estado !== 'vencido') {
            return 0;
        }
        return today()->diffInDays($this->fecha_vencimiento, false);
    }

    // Métodos
    public function estaVencido()
    {
        return $this->estado === 'activo' && $this->fecha_vencimiento < today();
    }

    public function devolver($userId, $estadoEquipo, $observaciones = null)
    {
        DB::beginTransaction();
        try {
            // Actualizar préstamo
            $this->update([
                'estado' => 'devuelto',
                'fecha_devolucion_real' => now(),
                'estado_equipo_devolucion' => $estadoEquipo,
                'observaciones_devolucion' => $observaciones,
                'recibido_por' => $userId,
            ]);

            // Registrar entrada en inventario
            MovimientoInventario::registrarEntrada([
                'item_id' => $this->item_id,
                'cantidad' => 1,
                'unidad_medida' => 'Unidad',
                'almacen_id' => $this->almacen_id,
                'responsable_id' => $userId,
                'motivo' => "Devolución de préstamo {$this->codigo}",
                'observaciones' => $observaciones,
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function cancelar($motivo)
    {
        DB::beginTransaction();
        try {
            $this->update([
                'estado' => 'cancelado',
                'observaciones_devolucion' => $motivo,
            ]);

            // Devolver al inventario
            MovimientoInventario::registrarEntrada([
                'item_id' => $this->item_id,
                'cantidad' => 1,
                'unidad_medida' => 'Unidad',
                'almacen_id' => $this->almacen_id,
                'responsable_id' => auth()->id(),
                'motivo' => "Cancelación de préstamo {$this->codigo}: {$motivo}",
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function enviarRecordatorio()
    {
        // TODO: Implementar envío de email/notificación
        $this->update([
            'recordatorio_enviado' => true,
            'fecha_recordatorio' => now(),
        ]);
        
        return true;
    }

    public static function generarCodigo()
    {
        $ultimoPrestamo = self::withTrashed()->orderBy('id', 'desc')->first();
        $numero = $ultimoPrestamo ? $ultimoPrestamo->id + 1 : 1;
        return 'PRE-' . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }

    public static function verificarVencimientos()
    {
        // Actualizar préstamos vencidos
        $prestamosVencidos = self::activos()
            ->where('fecha_vencimiento', '<', today())
            ->get();

        foreach ($prestamosVencidos as $prestamo) {
            $prestamo->update(['estado' => 'vencido']);
            
            if (!$prestamo->recordatorio_enviado) {
                $prestamo->enviarRecordatorio();
            }
        }

        return $prestamosVencidos->count();
    }

    public static function enviarRecordatoriosProximos($diasAntes = 3)
    {
        $prestamos = self::activos()
            ->whereDate('fecha_vencimiento', today()->addDays($diasAntes))
            ->where('recordatorio_enviado', false)
            ->get();

        foreach ($prestamos as $prestamo) {
            $prestamo->enviarRecordatorio();
        }

        return $prestamos->count();
    }
}
