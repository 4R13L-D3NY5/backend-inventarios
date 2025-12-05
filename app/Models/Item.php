<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'items';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'codigo',
        'nombre',
        'categoria_id',
        'subcategoria_id',
        'laboratorio_destino_id',
        'ubicacion_inicial_id',
        'marca',
        'modelo',
        'unidad_medida_base',
        'stock_inicial',
        'stock_minimo',
        'es_consumible',
        'es_peligroso',
        'descripcion',
        'especificaciones_tecnicas',
        'activo',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'stock_inicial' => 'decimal:2',
        'stock_minimo' => 'decimal:2',
        'es_consumible' => 'boolean',
        'es_peligroso' => 'boolean',
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación con categoría.
     */
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * Relación con subcategoría.
     */
    public function subcategoria()
    {
        return $this->belongsTo(Subcategoria::class);
    }

    /**
     * Relación con unidades de medida.
     */
    public function unidades()
    {
        return $this->hasMany(ItemUnidad::class);
    }

    /**
     * Relación con historial de precios.
     */
    public function historialPrecios()
    {
        return $this->hasMany(HistorialPrecio::class);
    }

    /**
     * Relación con inventario.
     */
    public function inventarios()
    {
        return $this->hasMany(Inventario::class);
    }

    /**
     * Relación con movimientos de inventario.
     */
    public function movimientosInventario()
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    /**
     * Relación con laboratorio de destino.
     */
    public function laboratorioDestino()
    {
        return $this->belongsTo(Laboratorio::class, 'laboratorio_destino_id');
    }

    /**
     * Relación con ubicación inicial.
     */
    public function ubicacionInicial()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_inicial_id');
    }

    /**
     * Scope para filtrar solo ítems activos.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para filtrar solo consumibles.
     */
    public function scopeConsumibles($query)
    {
        return $query->where('es_consumible', true);
    }

    /**
     * Scope para filtrar solo equipos.
     */
    public function scopeEquipos($query)
    {
        return $query->where('es_consumible', false);
    }

    /**
     * Scope para filtrar por categoría.
     */
    public function scopePorCategoria($query, $categoriaId)
    {
        return $query->where('categoria_id', $categoriaId);
    }

    /**
     * Scope para búsqueda por código, nombre o marca.
     */
    public function scopeBuscar($query, $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('codigo', 'like', "%{$termino}%")
              ->orWhere('nombre', 'like', "%{$termino}%")
              ->orWhere('marca', 'like', "%{$termino}%")
              ->orWhere('modelo', 'like', "%{$termino}%");
        });
    }
}
