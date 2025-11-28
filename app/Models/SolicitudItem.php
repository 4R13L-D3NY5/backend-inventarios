<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudItem extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'solicitud_items';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'solicitud_id',
        'item_id',
        'cantidad_solicitada',
        'unidad_medida',
        'observaciones',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'cantidad_solicitada' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con la solicitud.
     */
    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }

    /**
     * Relación con el ítem.
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
