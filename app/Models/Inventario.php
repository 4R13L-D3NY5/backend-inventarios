<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    use HasFactory;

    protected $table = 'inventarios';

    protected $fillable = [
        'almacen_id',
        'item_id',
        'cantidad_actual',
        'cantidad_reservada',
        'stock_minimo',
        'stock_maximo',
        'ultima_actualizacion',
    ];

    protected $casts = [
        'cantidad_actual' => 'decimal:2',
        'cantidad_reservada' => 'decimal:2',
        'stock_minimo' => 'decimal:2',
        'stock_maximo' => 'decimal:2',
        'ultima_actualizacion' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    // Accessors
    public function getCantidadDisponibleAttribute()
    {
        return $this->cantidad_actual - $this->cantidad_reservada;
    }

    // Scopes
    public function scopeBajoStock($query)
    {
        return $query->whereRaw('cantidad_actual < stock_minimo');
    }

    public function scopePorAlmacen($query, $almacenId)
    {
        return $query->where('almacen_id', $almacenId);
    }

    public function scopePorItem($query, $itemId)
    {
        return $query->where('item_id', $itemId);
    }

    // Métodos
    public function incrementar($cantidad)
    {
        $this->cantidad_actual += $cantidad;
        $this->ultima_actualizacion = now();
        $this->save();
    }

    public function decrementar($cantidad)
    {
        if ($this->cantidad_disponible < $cantidad) {
            throw new \Exception('Stock insuficiente. Disponible: ' . $this->cantidad_disponible);
        }
        
        $this->cantidad_actual -= $cantidad;
        $this->ultima_actualizacion = now();
        $this->save();
    }

    public function reservar($cantidad)
    {
        if ($this->cantidad_disponible < $cantidad) {
            throw new \Exception('Stock insuficiente para reservar');
        }
        
        $this->cantidad_reservada += $cantidad;
        $this->save();
    }

    public function liberarReserva($cantidad)
    {
        $this->cantidad_reservada -= $cantidad;
        if ($this->cantidad_reservada < 0) {
            $this->cantidad_reservada = 0;
        }
        $this->save();
    }

    public function esBajoStock()
    {
        return $this->cantidad_actual < $this->stock_minimo;
    }
}
