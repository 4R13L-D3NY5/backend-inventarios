<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Solicitud extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'solicitudes';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'codigo',
        'tipo',
        'prioridad',
        'solicitante_id',
        'laboratorio_id',
        'ubicacion_id',
        'observaciones',
        'fecha_solicitud',
        'fecha_completada',
        'estado_subalmacen',
        'estado_almacen',
        'estado_adquisicion',
        'estado_general',
        'aprobado_subalmacen_por',
        'aprobado_subalmacen_fecha',
        'aprobado_almacen_por',
        'aprobado_almacen_fecha',
        'aprobado_adquisicion_por',
        'aprobado_adquisicion_fecha',
        'motivo_denegacion',
        'denegado_por',
        'denegado_fecha',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_solicitud' => 'date',
        'fecha_completada' => 'datetime',
        'aprobado_subalmacen_fecha' => 'datetime',
        'aprobado_almacen_fecha' => 'datetime',
        'aprobado_adquisicion_fecha' => 'datetime',
        'denegado_fecha' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación con el solicitante.
     */
    public function solicitante()
    {
        return $this->belongsTo(Personal::class, 'solicitante_id');
    }

    /**
     * Relación con el laboratorio.
     */
    public function laboratorio()
    {
        return $this->belongsTo(Laboratorio::class);
    }

    /**
     * Relación con la ubicación.
     */
    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class);
    }

    /**
     * Relación con los ítems solicitados.
     */
    public function items()
    {
        return $this->hasMany(SolicitudItem::class);
    }

    /**
     * Relación con el aprobador de sub-almacén.
     */
    public function aprobadorSubalmacen()
    {
        return $this->belongsTo(User::class, 'aprobado_subalmacen_por');
    }

    /**
     * Relación con el aprobador de almacén.
     */
    public function aprobadorAlmacen()
    {
        return $this->belongsTo(User::class, 'aprobado_almacen_por');
    }

    /**
     * Relación con el aprobador de adquisición.
     */
    public function aprobadorAdquisicion()
    {
        return $this->belongsTo(User::class, 'aprobado_adquisicion_por');
    }

    /**
     * Relación con quien denegó la solicitud.
     */
    public function denegadoPor()
    {
        return $this->belongsTo(User::class, 'denegado_por');
    }

    /**
     * Scope para solicitudes pendientes de sub-almacén.
     */
    public function scopePendientesSubalmacen($query)
    {
        return $query->where('estado_general', 'pendiente_subalmacen');
    }

    /**
     * Scope para solicitudes pendientes de almacén.
     */
    public function scopePendientesAlmacen($query)
    {
        return $query->where('estado_general', 'pendiente_almacen');
    }

    /**
     * Scope para solicitudes en adquisiciones.
     */
    public function scopeEnAdquisiciones($query)
    {
        return $query->where('estado_general', 'en_adquisiciones');
    }

    /**
     * Scope para solicitudes completadas.
     */
    public function scopeCompletadas($query)
    {
        return $query->where('estado_general', 'completada');
    }

    /**
     * Scope para solicitudes denegadas.
     */
    public function scopeDenegadas($query)
    {
        return $query->where('estado_general', 'denegada');
    }

    /**
     * Scope para filtrar por prioridad.
     */
    public function scopePorPrioridad($query, $prioridad)
    {
        return $query->where('prioridad', $prioridad);
    }

    /**
     * Scope para filtrar por laboratorio.
     */
    public function scopePorLaboratorio($query, $laboratorioId)
    {
        return $query->where('laboratorio_id', $laboratorioId);
    }

    /**
     * Scope para filtrar por tipo.
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Obtener el total de ítems solicitados.
     */
    public function getTotalItemsAttribute()
    {
        return $this->items()->count();
    }

    /**
     * Aprobar en nivel sub-almacén.
     */
    public function aprobarSubalmacen($userId)
    {
        $this->update([
            'estado_subalmacen' => 'aprobado',
            'aprobado_subalmacen_por' => $userId,
            'aprobado_subalmacen_fecha' => now(),
            'estado_general' => 'pendiente_almacen',
            'estado_almacen' => 'pendiente',
        ]);
    }

    /**
     * Aprobar en nivel almacén.
     */
    public function aprobarAlmacen($userId)
    {
        $this->update([
            'estado_almacen' => 'aprobado',
            'aprobado_almacen_por' => $userId,
            'aprobado_almacen_fecha' => now(),
            'estado_general' => 'en_adquisiciones',
            'estado_adquisicion' => 'pendiente',
        ]);
    }

    /**
     * Aprobar en nivel adquisición.
     */
    public function aprobarAdquisicion($userId)
    {
        $this->update([
            'estado_adquisicion' => 'aprobado',
            'aprobado_adquisicion_por' => $userId,
            'aprobado_adquisicion_fecha' => now(),
            'estado_general' => 'completada',
            'fecha_completada' => now(),
        ]);
    }

    /**
     * Denegar la solicitud.
     */
    public function denegar($userId, $motivo)
    {
        $estadoActual = $this->estado_general;
        
        // Actualizar el estado del nivel actual a denegado
        if ($estadoActual === 'pendiente_subalmacen') {
            $this->estado_subalmacen = 'denegado';
        } elseif ($estadoActual === 'pendiente_almacen') {
            $this->estado_almacen = 'denegado';
        } elseif ($estadoActual === 'en_adquisiciones') {
            $this->estado_adquisicion = 'denegado';
        }

        $this->update([
            'estado_general' => 'denegada',
            'motivo_denegacion' => $motivo,
            'denegado_por' => $userId,
            'denegado_fecha' => now(),
        ]);
    }

    /**
     * Generar código único de solicitud.
     */
    public static function generarCodigo()
    {
        $ultimaSolicitud = self::withTrashed()->orderBy('id', 'desc')->first();
        $numero = $ultimaSolicitud ? $ultimaSolicitud->id + 1 : 1;
        return 'SOL-' . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }
}
