<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcategoria extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'subcategorias';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'categoria_id',
        'nombre',
        'descripcion',
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
    ];

    /**
     * Relación con categoría padre.
     */
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * Relación con ítems.
     */
    public function items()
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Scope para filtrar solo subcategorías activas.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para filtrar por categoría.
     */
    public function scopePorCategoria($query, $categoriaId)
    {
        return $query->where('categoria_id', $categoriaId);
    }

    /**
     * Scope para búsqueda por nombre.
     */
    public function scopeBuscar($query, $termino)
    {
        return $query->where('nombre', 'like', "%{$termino}%");
    }
}
