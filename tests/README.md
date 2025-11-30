# Guía de Pruebas - Sistema UNITEPC

Esta carpeta contiene scripts y herramientas para probar todos los endpoints del sistema de gestión de activos UNITEPC.

## 📁 Archivos de Prueba

### 1. Colección de Postman
**Archivo:** `UNITEPC_Inventory_System.postman_collection.json`

Colección completa con más de 30 endpoints organizados por módulos.

**Cómo usar:**
1. Abre Postman
2. Importa la colección: `File > Import > Select File`
3. Configura las variables:
   - `base_url`: `http://localhost/backend-inventarios/public/api`
   - `auth_token`: Se genera automáticamente al hacer login
4. Ejecuta las pruebas en orden o usa el Collection Runner

**Módulos incluidos:**
- ✅ Autenticación
- ✅ Proveedores
- ✅ Items
- ✅ Solicitudes
- ✅ Órdenes de Compra
- ✅ Inventario
- ✅ Movimientos de Inventario
- ✅ Préstamos

---

### 2. Script Bash (Linux/Mac)
**Archivo:** `api_tests.sh`

Script automatizado para ejecutar pruebas desde la terminal.

**Requisitos:**
- `curl` instalado
- `jq` instalado (para formatear JSON)

**Cómo usar:**
```bash
# Dar permisos de ejecución
chmod +x api_tests.sh

# Ejecutar
./api_tests.sh
```

**Características:**
- ✅ Output con colores (verde = éxito, rojo = error)
- ✅ Autenticación automática
- ✅ Pruebas secuenciales de todos los módulos
- ✅ Creación de datos de prueba

---

### 3. Script PowerShell (Windows)
**Archivo:** `api_tests.ps1`

Script automatizado para Windows con PowerShell.

**Cómo usar:**
```powershell
# Ejecutar
.\api_tests.ps1
```

**Características:**
- ✅ Output con colores
- ✅ Autenticación automática
- ✅ Manejo de errores
- ✅ Compatible con Windows 10/11

---

### 4. Tests PHPUnit
**Archivo:** `Feature/OrdenCompraTest.php`

Suite de pruebas unitarias para el módulo de Órdenes de Compra.

**Cómo usar:**
```bash
# Ejecutar todos los tests
php artisan test

# Ejecutar solo tests de Órdenes de Compra
php artisan test --filter OrdenCompraTest

# Ejecutar con coverage
php artisan test --coverage
```

**Tests incluidos:**
1. ✅ Listar órdenes de compra
2. ✅ Crear orden de compra
3. ✅ Aprobar orden
4. ✅ Confirmar orden
5. ✅ Registrar recepción
6. ✅ Cancelar orden
7. ✅ Validar restricciones de edición
8. ✅ Listar pendientes de recepción
9. ✅ Validar datos requeridos
10. ✅ Integración con inventario

---

## 🚀 Configuración Inicial

### 1. Configurar Base de Datos

Crear archivo `.env` con:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventarios
DB_USERNAME=root
DB_PASSWORD=
```

### 2. Crear Base de Datos

```sql
CREATE DATABASE inventarios CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Ejecutar Migraciones y Seeders

```bash
php artisan key:generate
php artisan migrate:fresh --seed
```

### 4. Iniciar Servidor

```bash
php artisan serve
```

O usar Laragon/XAMPP/WAMP.

---

## 📊 Flujo de Pruebas Recomendado

### Opción 1: Pruebas Manuales con Postman
1. Importar colección
2. Ejecutar "Login - Admin" para obtener token
3. Probar endpoints individualmente
4. Usar Collection Runner para pruebas automatizadas

### Opción 2: Pruebas Automatizadas con Scripts
1. Configurar base de datos
2. Ejecutar script bash o PowerShell
3. Revisar output con colores
4. Verificar resultados en la base de datos

### Opción 3: Pruebas Unitarias con PHPUnit
1. Configurar base de datos de pruebas
2. Ejecutar `php artisan test`
3. Revisar coverage y resultados
4. Agregar más tests según necesidad

---

## 🔐 Usuarios de Prueba

Los seeders crean los siguientes usuarios:

| Usuario | Password | Rol | Descripción |
|---------|----------|-----|-------------|
| admin | admin123 | Administrador | Acceso completo |
| lab.user | lab123 | Usuario | Usuario de laboratorio |
| almacen.user | almacen123 | Usuario | Usuario de almacén |

---

## 📝 Ejemplos de Pruebas

### Crear Orden de Compra

**Request:**
```http
POST /api/ordenes-compra
Authorization: Bearer {token}
Content-Type: application/json

{
    "proveedor_id": 1,
    "fecha_emision": "2025-11-29",
    "fecha_entrega_estimada": "2025-12-15",
    "condiciones_pago": "Pago contra entrega",
    "items": [
        {
            "item_id": 1,
            "cantidad_solicitada": 10,
            "unidad_medida": "L",
            "precio_unitario": 85.00
        }
    ]
}
```

**Response esperado:**
```json
{
    "message": "Orden de compra creada exitosamente",
    "data": {
        "id": 7,
        "numero_orden": "OC-2025-007",
        "estado": "borrador",
        "total": 850.00,
        "items": [...]
    }
}
```

### Aprobar Orden

**Request:**
```http
POST /api/ordenes-compra/7/aprobar
Authorization: Bearer {token}
```

**Response esperado:**
```json
{
    "message": "Orden de compra aprobada exitosamente",
    "data": {
        "id": 7,
        "estado": "enviada",
        "usuario_aprobador_id": 1,
        "fecha_aprobacion": "2025-11-29T20:30:00.000000Z"
    }
}
```

---

## 🐛 Solución de Problemas

### Error: "Unauthenticated"
- Verifica que el token esté incluido en el header
- Ejecuta login nuevamente para obtener un token válido

### Error: "Database connection failed"
- Verifica la configuración del `.env`
- Asegúrate de que MySQL esté corriendo
- Verifica que la base de datos exista

### Error: "SQLSTATE[42S02]: Base table or view not found"
- Ejecuta las migraciones: `php artisan migrate:fresh --seed`

### Error: "Class 'OrdenCompra' not found"
- Ejecuta: `composer dump-autoload`

---

## 📈 Métricas de Prueba

Al ejecutar las pruebas completas, deberías obtener:

- ✅ **30+ endpoints** probados
- ✅ **8 módulos** validados
- ✅ **100% de cobertura** en flujos principales
- ✅ **0 errores** en configuración correcta

---

## 🎯 Próximos Pasos

1. Ejecutar pruebas básicas con Postman
2. Validar flujo completo de Orden de Compra
3. Probar integración con inventario
4. Verificar reportes y consultas
5. Realizar pruebas de carga (opcional)

---

**Última actualización:** 2025-11-29  
**Sistema:** UNITEPC - Gestión de Activos  
**Versión:** 1.0
