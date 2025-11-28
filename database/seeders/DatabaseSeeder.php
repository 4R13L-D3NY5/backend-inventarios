<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Rol;
use App\Models\User;
use App\Models\Permiso;
use App\Models\Personal;
use App\Models\Proveedor;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\Item;
use App\Models\ItemUnidad;
use App\Models\HistorialPrecio;
use App\Models\Laboratorio;
use App\Models\Ubicacion;
use App\Models\Almacen;
use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\Prestamo;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles base
        $adminRole = Rol::create([
            'nombre'      => 'Administrador',
            'descripcion' => 'Acceso total al sistema',
            'estado'      => true,
        ]);

        $userRole = Rol::create([
            'nombre'      => 'Usuario',
            'descripcion' => 'Usuario estándar del sistema',
            'estado'      => true,
        ]);

        // 2. Personal (empleados/usuarios del sistema)
        $adminPersonal = Personal::create([
            'nombres' => 'Juan Carlos',
            'apellidos' => 'Pérez Mamani',
            'ci' => '1234567',
            'celular' => '+591 78945612',
            'estado' => true,
        ]);

        $labPersonal = Personal::create([
            'nombres' => 'María Elena',
            'apellidos' => 'Quispe Condori',
            'ci' => '7654321',
            'celular' => '+591 76543210',
            'estado' => true,
        ]);

        $almacenPersonal = Personal::create([
            'nombres' => 'Roberto',
            'apellidos' => 'Flores Gutiérrez',
            'ci' => '9876543',
            'celular' => '+591 71234567',
            'estado' => true,
        ]);

        // 3. Usuario administrador
        $adminUser = User::create([
            'usuario'             => 'admin',
            'email'               => 'admin@example.com',
            'password'            => Hash::make('admin123'), // cámbialo en producción
            'rol_id'              => $adminRole->id,
            'personal_id'         => $adminPersonal->id,
            'estado'              => true,
        ]);

        // Usuario de laboratorio
        User::create([
            'usuario'             => 'lab.user',
            'email'               => 'lab@example.com',
            'password'            => Hash::make('lab123'),
            'rol_id'              => $userRole->id,
            'personal_id'         => $labPersonal->id,
            'estado'              => true,
        ]);

        // Usuario de almacén
        User::create([
            'usuario'             => 'almacen.user',
            'email'               => 'almacen@example.com',
            'password'            => Hash::make('almacen123'),
            'rol_id'              => $userRole->id,
            'personal_id'         => $almacenPersonal->id,
            'estado'              => true,
        ]);
        
        // 4. Permisos de ejemplo (ajústalos según tus módulos)
        // $permisos = [
        //     [
        //         'rol_id'      => $adminRole->id,
        //         'nombre'      => 'Ver usuarios',
        //         'clave'       => 'users.index',
        //         'descripcion' => 'Listar usuarios del sistema',
        //         'estado'      => true,
        //     ],
        //     [
        //         'rol_id'      => $adminRole->id,
        //         'nombre'      => 'Crear usuarios',
        //         'clave'       => 'users.store',
        //         'descripcion' => 'Crear nuevos usuarios',
        //         'estado'      => true,
        //     ],
        //     [
        //         'rol_id'      => $adminRole->id,
        //         'nombre'      => 'Editar usuarios',
        //         'clave'       => 'users.update',
        //         'descripcion' => 'Editar usuarios existentes',
        //         'estado'      => true,
        //     ],
        //     [
        //         'rol_id'      => $adminRole->id,
        //         'nombre'      => 'Eliminar usuarios',
        //         'clave'       => 'users.destroy',
        //         'descripcion' => 'Eliminar usuarios',
        //         'estado'      => true,
        //     ],
        // ];

        // foreach ($permisos as $permiso) {
        //     Permiso::create($permiso);
        // }

        // 4. Proveedores de ejemplo
        $proveedores = [
            [
                'nombre' => 'Química del Sur SRL',
                'nit' => '1234567890',
                'telefono' => '+591 78945612',
                'email' => 'contacto@quimicasur.com',
                'direccion' => 'Av. América #123, La Paz',
                'observaciones' => 'Proveedor principal de reactivos químicos',
                'activo' => true,
            ],
            [
                'nombre' => 'LabEquip Bolivia',
                'nit' => '0987654321',
                'telefono' => '+591 76543210',
                'email' => 'ventas@labequip.bo',
                'direccion' => 'Calle Comercio #456, Cochabamba',
                'observaciones' => 'Especializado en equipos de laboratorio',
                'activo' => true,
            ],
            [
                'nombre' => 'BioInsumos Científicos',
                'nit' => null,
                'telefono' => '+591 71234567',
                'email' => 'info@bioinsumos.com',
                'direccion' => 'Zona Sur, Santa Cruz',
                'observaciones' => 'Proveedor internacional sin NIT local',
                'activo' => true,
            ],
            [
                'nombre' => 'TechSupply Bolivia',
                'nit' => '1122334455',
                'telefono' => '+591 72345678',
                'email' => 'soporte@techsupply.bo',
                'direccion' => 'Av. Blanco Galindo Km 4, Cochabamba',
                'observaciones' => null,
                'activo' => true,
            ],
            [
                'nombre' => 'CompuMundo',
                'nit' => '5544332211',
                'telefono' => '+591 78901234',
                'email' => 'ventas@compumundo.bo',
                'direccion' => 'Calle Ayacucho #789, La Paz',
                'observaciones' => 'Equipos de cómputo y tecnología',
                'activo' => true,
            ],
            [
                'nombre' => 'Reactivos Andinos SAC',
                'nit' => '6677889900',
                'telefono' => '+591 79012345',
                'email' => 'contacto@reactivosandinos.com',
                'direccion' => 'Av. Circunvalación #321, El Alto',
                'observaciones' => 'Reactivos de alta pureza',
                'activo' => true,
            ],
            [
                'nombre' => 'Instrumentos de Precisión Ltda',
                'nit' => '9988776655',
                'telefono' => '+591 70123456',
                'email' => null,
                'direccion' => 'Zona Central, Oruro',
                'observaciones' => 'Instrumentos de medición y calibración',
                'activo' => true,
            ],
            [
                'nombre' => 'Suministros Médicos del Sur',
                'nit' => '1231231234',
                'telefono' => '+591 77654321',
                'email' => 'info@sumedicosur.bo',
                'direccion' => 'Av. Banzer #555, Santa Cruz',
                'observaciones' => null,
                'activo' => false,
            ],
            [
                'nombre' => 'Cristalería Científica Bolivia',
                'nit' => '4564564567',
                'telefono' => '+591 76012345',
                'email' => 'ventas@cristaleria.bo',
                'direccion' => 'Calle Sucre #234, Sucre',
                'observaciones' => 'Material de vidrio para laboratorio',
                'activo' => true,
            ],
            [
                'nombre' => 'Equipos y Reactivos Profesionales',
                'nit' => '7897897890',
                'telefono' => '+591 75678901',
                'email' => 'contacto@equipospro.com',
                'direccion' => 'Av. Petrolera Km 7, Santa Cruz',
                'observaciones' => 'Distribuidor autorizado de marcas internacionales',
                'activo' => true,
            ],
        ];

        foreach ($proveedores as $proveedor) {
            Proveedor::create($proveedor);
        }

        // 5. Categorías
        $categorias = [
            ['nombre' => 'Reactivos Químicos', 'descripcion' => 'Sustancias químicas para análisis y experimentos', 'activo' => true],
            ['nombre' => 'Equipos de Laboratorio', 'descripcion' => 'Instrumentos y equipos científicos', 'activo' => true],
            ['nombre' => 'Material de Vidrio', 'descripcion' => 'Cristalería y material de vidrio', 'activo' => true],
            ['nombre' => 'Insumos Generales', 'descripcion' => 'Consumibles y materiales generales', 'activo' => true],
            ['nombre' => 'Equipos de Cómputo', 'descripcion' => 'Computadoras y accesorios', 'activo' => true],
        ];

        foreach ($categorias as $cat) {
            Categoria::create($cat);
        }

        // 6. Subcategorías
        $subcategorias = [
            ['categoria_id' => 1, 'nombre' => 'Ácidos', 'descripcion' => 'Ácidos inorgánicos y orgánicos', 'activo' => true],
            ['categoria_id' => 1, 'nombre' => 'Bases', 'descripcion' => 'Bases y álcalis', 'activo' => true],
            ['categoria_id' => 1, 'nombre' => 'Solventes', 'descripcion' => 'Solventes orgánicos', 'activo' => true],
            ['categoria_id' => 2, 'nombre' => 'Microscopios', 'descripcion' => 'Microscopios ópticos y digitales', 'activo' => true],
            ['categoria_id' => 2, 'nombre' => 'Pipetas', 'descripcion' => 'Pipetas automáticas y manuales', 'activo' => true],
            ['categoria_id' => 2, 'nombre' => 'Balanzas', 'descripcion' => 'Balanzas analíticas y de precisión', 'activo' => true],
            ['categoria_id' => 3, 'nombre' => 'Vasos y Beakers', 'descripcion' => 'Vasos de precipitado', 'activo' => true],
            ['categoria_id' => 3, 'nombre' => 'Matraces', 'descripcion' => 'Matraces volumétricos y aforados', 'activo' => true],
            ['categoria_id' => 4, 'nombre' => 'Guantes', 'descripcion' => 'Guantes de protección', 'activo' => true],
            ['categoria_id' => 4, 'nombre' => 'Mascarillas', 'descripcion' => 'Mascarillas y protección respiratoria', 'activo' => true],
            ['categoria_id' => 5, 'nombre' => 'Laptops', 'descripcion' => 'Computadoras portátiles', 'activo' => true],
            ['categoria_id' => 5, 'nombre' => 'Accesorios', 'descripcion' => 'Mouse, teclados, etc.', 'activo' => true],
        ];

        foreach ($subcategorias as $subcat) {
            Subcategoria::create($subcat);
        }

        // // 7. Ítems
        // $items = [
        //     // Reactivos Químicos - Ácidos
        //     [
        //         'codigo' => 'QUI-001',
        //         'nombre' => 'Ácido Sulfúrico H2SO4 1L',
        //         'categoria_id' => 1,
        //         'subcategoria_id' => 1,
        //         'marca' => 'Merck',
        //         'modelo' => 'H2SO4 98%',
        //         'unidad_medida_base' => 'mL',
        //         'es_consumible' => true,
        //         'es_peligroso' => true,
        //         'descripcion' => 'Ácido sulfúrico concentrado 98%',
        //         'activo' => true,
        //     ],
        //     [
        //         'codigo' => 'QUI-002',
        //         'nombre' => 'Ácido Clorhídrico HCl 1L',
        //         'categoria_id' => 1,
        //         'subcategoria_id' => 1,
        //         'marca' => 'Merck',
        //         'modelo' => 'HCl 37%',
        //         'unidad_medida_base' => 'mL',
        //         'es_consumible' => true,
        //         'es_peligroso' => true,
        //         'descripcion' => 'Ácido clorhídrico concentrado 37%',
        //         'activo' => true,
        //     ],
        //     // Bases
        //     [
        //         'codigo' => 'QUI-003',
        //         'nombre' => 'Hidróxido de Sodio NaOH 1kg',
        //         'categoria_id' => 1,
        //         'subcategoria_id' => 2,
        //         'marca' => 'Sigma-Aldrich',
        //         'modelo' => 'NaOH 99%',
        //         'unidad_medida_base' => 'g',
        //         'es_consumible' => true,
        //         'es_peligroso' => true,
        //         'descripcion' => 'Hidróxido de sodio en perlas',
        //         'activo' => true,
        //     ],
        //     // Solventes
        //     [
        //         'codigo' => 'QUI-004',
        //         'nombre' => 'Etanol 96% 1L',
        //         'categoria_id' => 1,
        //         'subcategoria_id' => 3,
        //         'marca' => 'Merck',
        //         'modelo' => 'Etanol 96%',
        //         'unidad_medida_base' => 'mL',
        //         'es_consumible' => true,
        //         'es_peligroso' => false,
        //         'descripcion' => 'Alcohol etílico 96%',
        //         'activo' => true,
        //     ],
        //     // Equipos - Microscopios
        //     [
        //         'codigo' => 'EQ-001',
        //         'nombre' => 'Microscopio Binocular',
        //         'categoria_id' => 2,
        //         'subcategoria_id' => 4,
        //         'marca' => 'Olympus',
        //         'modelo' => 'CX23',
        //         'unidad_medida_base' => 'UN',
        //         'es_consumible' => false,
        //         'es_peligroso' => false,
        //         'descripcion' => 'Microscopio binocular con aumentos 40x-1000x',
        //         'activo' => true,
        //     ],
        //     // Pipetas
        //     [
        //         'codigo' => 'EQ-002',
        //         'nombre' => 'Pipeta Automática 10ml',
        //         'categoria_id' => 2,
        //         'subcategoria_id' => 5,
        //         'marca' => 'Eppendorf',
        //         'modelo' => 'Research Plus',
        //         'unidad_medida_base' => 'UN',
        //         'es_consumible' => false,
        //         'es_peligroso' => false,
        //         'descripcion' => 'Pipeta automática de volumen variable 1-10ml',
        //         'activo' => true,
        //     ],
        //     // Balanzas
        //     [
        //         'codigo' => 'EQ-003',
        //         'nombre' => 'Balanza Analítica',
        //         'categoria_id' => 2,
        //         'subcategoria_id' => 6,
        //         'marca' => 'Ohaus',
        //         'modelo' => 'Pioneer PA214',
        //         'unidad_medida_base' => 'UN',
        //         'es_consumible' => false,
        //         'es_peligroso' => false,
        //         'descripcion' => 'Balanza analítica 210g x 0.0001g',
        //         'activo' => true,
        //     ],
        //     // Material de Vidrio
        //     [
        //         'codigo' => 'VID-001',
        //         'nombre' => 'Vaso de Precipitado 250ml',
        //         'categoria_id' => 3,
        //         'subcategoria_id' => 7,
        //         'marca' => 'Pyrex',
        //         'modelo' => 'Borosilicato',
        //         'unidad_medida_base' => 'UN',
        //         'es_consumible' => true,
        //         'es_peligroso' => false,
        //         'descripcion' => 'Vaso de precipitado de vidrio borosilicato',
        //         'activo' => true,
        //     ],
        //     [
        //         'codigo' => 'VID-002',
        //         'nombre' => 'Matraz Aforado 100ml',
        //         'categoria_id' => 3,
        //         'subcategoria_id' => 8,
        //         'marca' => 'Pyrex',
        //         'modelo' => 'Clase A',
        //         'unidad_medida_base' => 'UN',
        //         'es_consumible' => true,
        //         'es_peligroso' => false,
        //         'descripcion' => 'Matraz volumétrico clase A',
        //         'activo' => true,
        //     ],
        //     // Insumos
        //     [
        //         'codigo' => 'INS-001',
        //         'nombre' => 'Guantes de Látex (100 unid)',
        //         'categoria_id' => 4,
        //         'subcategoria_id' => 9,
        //         'marca' => 'Kimberly-Clark',
        //         'modelo' => 'Talla M',
        //         'unidad_medida_base' => 'UN',
        //         'es_consumible' => true,
        //         'es_peligroso' => false,
        //         'descripcion' => 'Caja de 100 guantes de látex',
        //         'activo' => true,
        //     ],
        //     // Equipos de Cómputo
        //     [
        //         'codigo' => 'COMP-001',
        //         'nombre' => 'Laptop Dell Latitude 5420',
        //         'categoria_id' => 5,
        //         'subcategoria_id' => 11,
        //         'marca' => 'Dell',
        //         'modelo' => 'Latitude 5420',
        //         'unidad_medida_base' => 'UN',
        //         'es_consumible' => false,
        //         'es_peligroso' => false,
        //         'descripcion' => 'Laptop Core i5, 8GB RAM, 256GB SSD',
        //         'activo' => true,
        //     ],
        // ];

        // foreach ($items as $itemData) {
        //     Item::create($itemData);
        // }

        // // 8. Conversiones de Unidades
        // $conversiones = [
        //     // Ácido Sulfúrico: mL base
        //     ['item_id' => 1, 'unidad_medida' => 'L', 'factor_a_base' => 1000],
        //     // Ácido Clorhídrico: mL base
        //     ['item_id' => 2, 'unidad_medida' => 'L', 'factor_a_base' => 1000],
        //     // Hidróxido de Sodio: g base
        //     ['item_id' => 3, 'unidad_medida' => 'kg', 'factor_a_base' => 1000],
        //     // Etanol: mL base
        //     ['item_id' => 4, 'unidad_medida' => 'L', 'factor_a_base' => 1000],
        //     // Guantes: UN base
        //     ['item_id' => 10, 'unidad_medida' => 'caja', 'factor_a_base' => 100],
        // ];

        // foreach ($conversiones as $conv) {
        //     ItemUnidad::create($conv);
        // }

        // // 9. Historial de Precios (múltiples proveedores y fechas)
        // $precios = [
        //     // Ácido Sulfúrico - múltiples proveedores
        //     ['item_id' => 1, 'proveedor_id' => 1, 'precio' => 85.00, 'fecha_vigencia' => '2025-11-01', 'moneda' => 'BOB'],
        //     ['item_id' => 1, 'proveedor_id' => 1, 'precio' => 88.00, 'fecha_vigencia' => '2025-10-28', 'moneda' => 'BOB'],
        //     ['item_id' => 1, 'proveedor_id' => 2, 'precio' => 92.00, 'fecha_vigencia' => '2025-10-20', 'moneda' => 'BOB'],
        //     ['item_id' => 1, 'proveedor_id' => 3, 'precio' => 90.00, 'fecha_vigencia' => '2025-10-15', 'moneda' => 'BOB'],
            
        //     // Ácido Clorhídrico
        //     ['item_id' => 2, 'proveedor_id' => 1, 'precio' => 75.00, 'fecha_vigencia' => '2025-11-01', 'moneda' => 'BOB'],
        //     ['item_id' => 2, 'proveedor_id' => 2, 'precio' => 78.00, 'fecha_vigencia' => '2025-10-25', 'moneda' => 'BOB'],
            
        //     // Hidróxido de Sodio
        //     ['item_id' => 3, 'proveedor_id' => 1, 'precio' => 120.00, 'fecha_vigencia' => '2025-11-01', 'moneda' => 'BOB'],
        //     ['item_id' => 3, 'proveedor_id' => 6, 'precio' => 125.00, 'fecha_vigencia' => '2025-10-20', 'moneda' => 'BOB'],
            
        //     // Etanol
        //     ['item_id' => 4, 'proveedor_id' => 1, 'precio' => 65.00, 'fecha_vigencia' => '2025-11-01', 'moneda' => 'BOB'],
        //     ['item_id' => 4, 'proveedor_id' => 3, 'precio' => 68.00, 'fecha_vigencia' => '2025-10-18', 'moneda' => 'BOB'],
            
        //     // Microscopio Binocular
        //     ['item_id' => 5, 'proveedor_id' => 2, 'precio' => 2800.00, 'fecha_vigencia' => '2025-10-28', 'moneda' => 'BOB'],
        //     ['item_id' => 5, 'proveedor_id' => 3, 'precio' => 3100.00, 'fecha_vigencia' => '2025-10-20', 'moneda' => 'BOB'],
            
        //     // Pipeta Automática
        //     ['item_id' => 6, 'proveedor_id' => 2, 'precio' => 450.00, 'fecha_vigencia' => '2025-10-10', 'moneda' => 'BOB'],
        //     ['item_id' => 6, 'proveedor_id' => 10, 'precio' => 470.00, 'fecha_vigencia' => '2025-10-05', 'moneda' => 'BOB'],
            
        //     // Balanza Analítica
        //     ['item_id' => 7, 'proveedor_id' => 2, 'precio' => 3500.00, 'fecha_vigencia' => '2025-11-01', 'moneda' => 'BOB'],
        //     ['item_id' => 7, 'proveedor_id' => 10, 'precio' => 3600.00, 'fecha_vigencia' => '2025-10-15', 'moneda' => 'BOB'],
            
        //     // Vaso de Precipitado
        //     ['item_id' => 8, 'proveedor_id' => 9, 'precio' => 35.00, 'fecha_vigencia' => '2025-10-20', 'moneda' => 'BOB'],
        //     ['item_id' => 8, 'proveedor_id' => 3, 'precio' => 38.00, 'fecha_vigencia' => '2025-10-10', 'moneda' => 'BOB'],
            
        //     // Guantes de Látex
        //     ['item_id' => 10, 'proveedor_id' => 3, 'precio' => 120.00, 'fecha_vigencia' => '2025-10-15', 'moneda' => 'BOB'],
        //     ['item_id' => 10, 'proveedor_id' => 8, 'precio' => 115.00, 'fecha_vigencia' => '2025-10-10', 'moneda' => 'BOB'],
            
        //     // Laptop Dell
        //     ['item_id' => 11, 'proveedor_id' => 4, 'precio' => 7500.00, 'fecha_vigencia' => '2025-10-28', 'moneda' => 'BOB'],
        //     ['item_id' => 11, 'proveedor_id' => 5, 'precio' => 7800.00, 'fecha_vigencia' => '2025-10-10', 'moneda' => 'BOB'],
        // ];

        // foreach ($precios as $precio) {
        //     HistorialPrecio::create($precio);
        // }

        // 10. Laboratorios
        $laboratorios = [
            ['nombre' => 'Laboratorio de Química', 'codigo' => 'LAB-QUI', 'descripcion' => 'Laboratorio de química general', 'activo' => true],
            ['nombre' => 'Laboratorio de Física', 'codigo' => 'LAB-FIS', 'descripcion' => 'Laboratorio de física experimental', 'activo' => true],
            ['nombre' => 'Laboratorio de Biología', 'codigo' => 'LAB-BIO', 'descripcion' => 'Laboratorio de biología y microbiología', 'activo' => true],
            ['nombre' => 'Laboratorio de Cómputo', 'codigo' => 'LAB-COMP', 'descripcion' => 'Sala de computación', 'activo' => true],
        ];

        foreach ($laboratorios as $lab) {
            Laboratorio::create($lab);
        }

        // 11. Ubicaciones
        $ubicaciones = [
            ['nombre' => 'Edificio A - Piso 1', 'codigo' => 'ED-A-P1', 'descripcion' => 'Primer piso edificio A', 'activo' => true],
            ['nombre' => 'Edificio A - Piso 2', 'codigo' => 'ED-A-P2', 'descripcion' => 'Segundo piso edificio A', 'activo' => true],
            ['nombre' => 'Edificio B - Piso 1', 'codigo' => 'ED-B-P1', 'descripcion' => 'Primer piso edificio B', 'activo' => true],
            ['nombre' => 'Almacén Central', 'codigo' => 'ALM-CENTRAL', 'descripcion' => 'Almacén principal', 'activo' => true],
        ];

        foreach ($ubicaciones as $ubi) {
            Ubicacion::create($ubi);
        }

        // 12. Almacenes
        $almacenes = [
            [
                'nombre' => 'Almacén Central',
                'codigo' => 'ALM-001',
                'tipo' => 'principal',
                'almacen_padre_id' => null,
                'ubicacion_id' => 4,
                'responsable_id' => 3,
                'descripcion' => 'Almacén principal de la institución',
                'activo' => true,
            ],
            [
                'nombre' => 'Sub-Almacén Química',
                'codigo' => 'SUB-QUI',
                'tipo' => 'subalmacen',
                'almacen_padre_id' => 1,
                'ubicacion_id' => 1,
                'responsable_id' => 2,
                'descripcion' => 'Subalmacén del laboratorio de química',
                'activo' => true,
            ],
            [
                'nombre' => 'Sub-Almacén Física',
                'codigo' => 'SUB-FIS',
                'tipo' => 'subalmacen',
                'almacen_padre_id' => 1,
                'ubicacion_id' => 2,
                'responsable_id' => 2,
                'descripcion' => 'Subalmacén del laboratorio de física',
                'activo' => true,
            ],
        ];

        foreach ($almacenes as $alm) {
            Almacen::create($alm);
        }

        $this->command->info('✅ Seeders completados: Usuarios, Proveedores, Categorías, Laboratorios, Ubicaciones y Almacenes');

    }
}
