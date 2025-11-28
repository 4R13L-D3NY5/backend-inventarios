<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Almacen extends Model
{
    use HasFactory;

    protected $table = 'almacenes';

    protected $fillable = [
        'nombre',
        'codigo',
        'tipo',
        'almacen_padre_id',
        'ubicacion_id',
        'responsable_id',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function almacenPadre()
    {
        return $this->belongsTo(Almacen::class, 'almacen_padre_id');
    }

    public function subalmacenes()
    {
        return $this->hasMany(Almacen::class, 'almacen_padre_id');
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class);
    }

    public function responsable()
    {
        return $this->belongsTo(Personal::class, 'responsable_id');
    }

    public function inventarios()
    {
        return $this->hasMany(Inventario::class);
    }

    public function movimientosOrigen()
    {
        return $this->hasMany(MovimientoInventario::class, 'almacen_origen_id');
    }

    public function movimientosDestino()
    {
        return $this->hasMany(MovimientoInventario::class, 'almacen_destino_id');
    }

    // Scopes
    public function scopePrincipales($query)
    {
        return $query->where('tipo', 'principal');
    }

    public function scopeSubalmacenes($query)
    {
        return $query->where('tipo', 'subalmacen');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    // Accessors
    public function getTotalItemsAttribute()
    {
        return $this->inventarios()->count();
    }

    public function getCantidadTotalAttribute()
    {
        return $this->inventarios()->sum('cantidad_actual');
    }

    public function getValorTotalAttribute()
    {
        $total = 0;
        foreach ($this->inventarios as $inventario) {
            $precioPromedio = $inventario->item->historialPrecios()
                ->orderBy('fecha_vigencia', 'desc')
                ->first()?->precio ?? 0;
            $total += $inventario->cantidad_actual * $precioPromedio;
        }
        return $total;
    }
}
