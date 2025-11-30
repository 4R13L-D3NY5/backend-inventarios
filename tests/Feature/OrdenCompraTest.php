<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Proveedor;
use App\Models\Item;
use App\Models\Almacen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class OrdenCompraTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $proveedor;
    protected $item;
    protected $almacen;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ejecutar seeders
        $this->seed();
        
        // Autenticar usuario
        $this->user = User::first();
        Sanctum::actingAs($this->user);
        
        // Obtener datos de prueba
        $this->proveedor = Proveedor::first();
        $this->item = Item::first();
        $this->almacen = Almacen::first();
    }

    /** @test */
    public function puede_listar_ordenes_de_compra()
    {
        $response = $this->getJson('/api/ordenes-compra');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'numero_orden',
                             'estado',
                             'proveedor',
                             'total'
                         ]
                     ]
                 ]);
    }

    /** @test */
    public function puede_crear_orden_de_compra()
    {
        $data = [
            'proveedor_id' => $this->proveedor->id,
            'fecha_emision' => now()->format('Y-m-d'),
            'fecha_entrega_estimada' => now()->addDays(15)->format('Y-m-d'),
            'condiciones_pago' => 'Pago contra entrega',
            'items' => [
                [
                    'item_id' => $this->item->id,
                    'cantidad_solicitada' => 10,
                    'unidad_medida' => 'L',
                    'precio_unitario' => 85.00
                ]
            ]
        ];

        $response = $this->postJson('/api/ordenes-compra', $data);
        
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'data' => [
                         'id',
                         'numero_orden',
                         'estado',
                         'total',
                         'items'
                     ]
                 ]);
        
        $this->assertDatabaseHas('ordenes_compra', [
            'proveedor_id' => $this->proveedor->id,
            'estado' => 'borrador'
        ]);
    }

    /** @test */
    public function puede_aprobar_orden_de_compra()
    {
        // Crear orden
        $orden = \App\Models\OrdenCompra::create([
            'numero_orden' => 'OC-TEST-001',
            'proveedor_id' => $this->proveedor->id,
            'fecha_emision' => now(),
            'estado' => 'borrador',
            'usuario_creador_id' => $this->user->id,
            'moneda' => 'BOB',
            'subtotal' => 0,
            'impuestos' => 0,
            'total' => 0
        ]);

        // Agregar item
        \App\Models\OrdenCompraItem::create([
            'orden_compra_id' => $orden->id,
            'item_id' => $this->item->id,
            'cantidad_solicitada' => 5,
            'unidad_medida' => 'L',
            'precio_unitario' => 85.00,
            'subtotal' => 425.00
        ]);

        $orden->calcularTotales();

        // Aprobar
        $response = $this->postJson("/api/ordenes-compra/{$orden->id}/aprobar");
        
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('ordenes_compra', [
            'id' => $orden->id,
            'estado' => 'enviada',
            'usuario_aprobador_id' => $this->user->id
        ]);
    }

    /** @test */
    public function puede_confirmar_orden_de_compra()
    {
        // Crear orden en estado enviada
        $orden = \App\Models\OrdenCompra::create([
            'numero_orden' => 'OC-TEST-002',
            'proveedor_id' => $this->proveedor->id,
            'fecha_emision' => now(),
            'estado' => 'enviada',
            'usuario_creador_id' => $this->user->id,
            'usuario_aprobador_id' => $this->user->id,
            'moneda' => 'BOB',
            'subtotal' => 425.00,
            'impuestos' => 0,
            'total' => 425.00
        ]);

        // Confirmar
        $response = $this->postJson("/api/ordenes-compra/{$orden->id}/confirmar");
        
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('ordenes_compra', [
            'id' => $orden->id,
            'estado' => 'confirmada'
        ]);
    }

    /** @test */
    public function puede_registrar_recepcion()
    {
        // Crear orden confirmada con items
        $orden = \App\Models\OrdenCompra::create([
            'numero_orden' => 'OC-TEST-003',
            'proveedor_id' => $this->proveedor->id,
            'fecha_emision' => now(),
            'estado' => 'confirmada',
            'usuario_creador_id' => $this->user->id,
            'moneda' => 'BOB',
            'subtotal' => 850.00,
            'impuestos' => 0,
            'total' => 850.00
        ]);

        $ordenItem = \App\Models\OrdenCompraItem::create([
            'orden_compra_id' => $orden->id,
            'item_id' => $this->item->id,
            'cantidad_solicitada' => 10,
            'cantidad_recibida' => 0,
            'unidad_medida' => 'L',
            'precio_unitario' => 85.00,
            'subtotal' => 850.00
        ]);

        // Registrar recepción
        $data = [
            'almacen_id' => $this->almacen->id,
            'items' => [
                [
                    'orden_compra_item_id' => $ordenItem->id,
                    'cantidad' => 10
                ]
            ]
        ];

        $response = $this->postJson("/api/ordenes-compra/{$orden->id}/recepcion", $data);
        
        $response->assertStatus(200);
        
        // Verificar que se actualizó la cantidad recibida
        $this->assertDatabaseHas('orden_compra_items', [
            'id' => $ordenItem->id,
            'cantidad_recibida' => 10
        ]);
        
        // Verificar que se creó movimiento de inventario
        $this->assertDatabaseHas('movimientos_inventario', [
            'orden_compra_id' => $orden->id,
            'tipo' => 'entrada',
            'item_id' => $this->item->id,
            'cantidad' => 10
        ]);
        
        // Verificar que el estado cambió a recibida_completa
        $this->assertDatabaseHas('ordenes_compra', [
            'id' => $orden->id,
            'estado' => 'recibida_completa'
        ]);
    }

    /** @test */
    public function puede_cancelar_orden_de_compra()
    {
        $orden = \App\Models\OrdenCompra::create([
            'numero_orden' => 'OC-TEST-004',
            'proveedor_id' => $this->proveedor->id,
            'fecha_emision' => now(),
            'estado' => 'enviada',
            'usuario_creador_id' => $this->user->id,
            'moneda' => 'BOB',
            'subtotal' => 0,
            'impuestos' => 0,
            'total' => 0
        ]);

        $data = [
            'motivo' => 'Proveedor no puede cumplir con los tiempos'
        ];

        $response = $this->postJson("/api/ordenes-compra/{$orden->id}/cancelar", $data);
        
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('ordenes_compra', [
            'id' => $orden->id,
            'estado' => 'cancelada'
        ]);
    }

    /** @test */
    public function no_puede_editar_orden_no_borrador()
    {
        $orden = \App\Models\OrdenCompra::create([
            'numero_orden' => 'OC-TEST-005',
            'proveedor_id' => $this->proveedor->id,
            'fecha_emision' => now(),
            'estado' => 'enviada',
            'usuario_creador_id' => $this->user->id,
            'moneda' => 'BOB',
            'subtotal' => 0,
            'impuestos' => 0,
            'total' => 0
        ]);

        $data = [
            'condiciones_pago' => 'Nuevas condiciones'
        ];

        $response = $this->putJson("/api/ordenes-compra/{$orden->id}", $data);
        
        $response->assertStatus(422)
                 ->assertJson([
                     'message' => 'Solo se pueden editar órdenes en estado borrador'
                 ]);
    }

    /** @test */
    public function puede_listar_ordenes_pendientes_de_recepcion()
    {
        // Crear orden confirmada
        \App\Models\OrdenCompra::create([
            'numero_orden' => 'OC-TEST-006',
            'proveedor_id' => $this->proveedor->id,
            'fecha_emision' => now(),
            'estado' => 'confirmada',
            'usuario_creador_id' => $this->user->id,
            'moneda' => 'BOB',
            'subtotal' => 0,
            'impuestos' => 0,
            'total' => 0
        ]);

        $response = $this->getJson('/api/ordenes-compra/pendientes/recepcion');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     '*' => [
                         'id',
                         'numero_orden',
                         'estado',
                         'proveedor'
                     ]
                 ]);
    }

    /** @test */
    public function valida_datos_requeridos_al_crear_orden()
    {
        $response = $this->postJson('/api/ordenes-compra', []);
        
        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'proveedor_id',
                     'fecha_emision',
                     'items'
                 ]);
    }
}
