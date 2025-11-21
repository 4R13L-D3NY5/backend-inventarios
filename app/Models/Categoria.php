<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'categorias';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
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
     * Relación con subcategorías.
     */
    public function subcategorias()
    {
        return $this->hasMany(Subcategoria::class);
    }

    /**
     * Relación con ítems.
     */
    public function items()
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Scope para filtrar solo categorías activas.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para búsqueda por nombre.
     */
    public function scopeBuscar($query, $termino)
    {
        return $query->where('nombre', 'like', "%{$termino}%");
    }
}
