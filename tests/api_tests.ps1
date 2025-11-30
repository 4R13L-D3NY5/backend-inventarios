# Script de pruebas automatizadas para el Sistema UNITEPC
# PowerShell version para Windows

$BaseUrl = "http://localhost/backend-inventarios/public/api"
$Token = ""

Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "UNITEPC - Sistema de Gestión de Activos" -ForegroundColor Cyan
Write-Host "Script de Pruebas Automatizadas" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

# Función para hacer peticiones HTTP
function Invoke-ApiRequest {
    param(
        [string]$Method,
        [string]$Endpoint,
        [object]$Body = $null,
        [string]$Description
    )
    
    Write-Host "Probando: $Description" -ForegroundColor Yellow
    
    $headers = @{
        "Content-Type" = "application/json"
    }
    
    if ($Token) {
        $headers["Authorization"] = "Bearer $Token"
    }
    
    try {
        $params = @{
            Uri = "$BaseUrl$Endpoint"
            Method = $Method
            Headers = $headers
        }
        
        if ($Body) {
            $params["Body"] = ($Body | ConvertTo-Json -Depth 10)
        }
        
        $response = Invoke-RestMethod @params
        
        Write-Host "✓ $Description - OK" -ForegroundColor Green
        $response | ConvertTo-Json -Depth 5
        Write-Host ""
        
        return $response
    }
    catch {
        Write-Host "✗ $Description - ERROR" -ForegroundColor Red
        Write-Host $_.Exception.Message -ForegroundColor Red
        Write-Host ""
        return $null
    }
}

# 1. AUTENTICACIÓN
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "1. PRUEBAS DE AUTENTICACIÓN" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

$loginData = @{
    usuario = "admin"
    password = "admin123"
}

$loginResponse = Invoke-ApiRequest -Method "POST" -Endpoint "/login" -Body $loginData -Description "Login de usuario"

if ($loginResponse -and $loginResponse.token) {
    $Token = $loginResponse.token
    Write-Host "Token obtenido: $($Token.Substring(0, 20))..." -ForegroundColor Green
} else {
    Write-Host "ERROR: No se pudo obtener el token de autenticación" -ForegroundColor Red
    exit 1
}

Write-Host ""

# Verificar usuario actual
Invoke-ApiRequest -Method "GET" -Endpoint "/me" -Description "Obtener usuario actual"

# 2. PROVEEDORES
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "2. PRUEBAS DE PROVEEDORES" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

Invoke-ApiRequest -Method "GET" -Endpoint "/proveedores" -Description "Listar proveedores"

$proveedorData = @{
    nombre = "Proveedor Test PowerShell"
    nit = "8888888888"
    telefono = "+591 71111111"
    email = "test@powershell.com"
    direccion = "Av. PowerShell #888"
    activo = $true
}

Invoke-ApiRequest -Method "POST" -Endpoint "/proveedores" -Body $proveedorData -Description "Crear nuevo proveedor"

# 3. ITEMS
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "3. PRUEBAS DE ITEMS" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

Invoke-ApiRequest -Method "GET" -Endpoint "/items" -Description "Listar items"

Invoke-ApiRequest -Method "GET" -Endpoint "/inventario/bajo-stock" -Description "Items bajo stock mínimo"

# 4. SOLICITUDES
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "4. PRUEBAS DE SOLICITUDES" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

Invoke-ApiRequest -Method "GET" -Endpoint "/solicitudes" -Description "Listar solicitudes"

$solicitudData = @{
    laboratorio_id = 1
    fecha_necesidad = "2025-12-20"
    justificacion = "Prueba automatizada PowerShell"
    items = @(
        @{
            item_id = 1
            cantidad_solicitada = 3
            unidad_medida = "L"
        }
    )
}

$solicitudResponse = Invoke-ApiRequest -Method "POST" -Endpoint "/solicitudes" -Body $solicitudData -Description "Crear solicitud"

if ($solicitudResponse -and $solicitudResponse.data.id) {
    $solicitudId = $solicitudResponse.data.id
    Write-Host "Solicitud creada con ID: $solicitudId" -ForegroundColor Green
    
    # Aprobar en nivel 1
    Invoke-ApiRequest -Method "POST" -Endpoint "/solicitudes/$solicitudId/aprobar-subalmacen" -Description "Aprobar solicitud - Subalmacén"
}

# 5. ÓRDENES DE COMPRA
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "5. PRUEBAS DE ÓRDENES DE COMPRA" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

Invoke-ApiRequest -Method "GET" -Endpoint "/ordenes-compra" -Description "Listar órdenes de compra"

$ordenData = @{
    proveedor_id = 1
    fecha_emision = (Get-Date -Format "yyyy-MM-dd")
    fecha_entrega_estimada = (Get-Date).AddDays(15).ToString("yyyy-MM-dd")
    condiciones_pago = "Pago contra entrega"
    items = @(
        @{
            item_id = 1
            cantidad_solicitada = 15
            unidad_medida = "L"
            precio_unitario = 85.00
        }
    )
}

$ordenResponse = Invoke-ApiRequest -Method "POST" -Endpoint "/ordenes-compra" -Body $ordenData -Description "Crear orden de compra"

if ($ordenResponse -and $ordenResponse.data.id) {
    $ordenId = $ordenResponse.data.id
    Write-Host "Orden de compra creada con ID: $ordenId" -ForegroundColor Green
    
    # Aprobar orden
    Invoke-ApiRequest -Method "POST" -Endpoint "/ordenes-compra/$ordenId/aprobar" -Description "Aprobar orden de compra"
    
    # Confirmar orden
    Invoke-ApiRequest -Method "POST" -Endpoint "/ordenes-compra/$ordenId/confirmar" -Description "Confirmar orden de compra"
    
    # Listar pendientes
    Invoke-ApiRequest -Method "GET" -Endpoint "/ordenes-compra/pendientes/recepcion" -Description "Órdenes pendientes de recepción"
}

# 6. INVENTARIO
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "6. PRUEBAS DE INVENTARIO" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

Invoke-ApiRequest -Method "GET" -Endpoint "/inventario/stock-detallado" -Description "Stock detallado"

Invoke-ApiRequest -Method "GET" -Endpoint "/inventario/por-categoria" -Description "Stock por categoría"

Invoke-ApiRequest -Method "GET" -Endpoint "/inventario/valoracion" -Description "Valoración de inventario"

Invoke-ApiRequest -Method "GET" -Endpoint "/inventario/kardex/1" -Description "Kardex del item 1"

# 7. MOVIMIENTOS
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "7. PRUEBAS DE MOVIMIENTOS" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

Invoke-ApiRequest -Method "GET" -Endpoint "/movimientos-inventario" -Description "Listar movimientos"

# 8. PRÉSTAMOS
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "8. PRUEBAS DE PRÉSTAMOS" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

Invoke-ApiRequest -Method "GET" -Endpoint "/prestamos" -Description "Listar préstamos"

Invoke-ApiRequest -Method "GET" -Endpoint "/prestamos/activos/lista" -Description "Préstamos activos"

Invoke-ApiRequest -Method "GET" -Endpoint "/prestamos/vencidos/lista" -Description "Préstamos vencidos"

# RESUMEN
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "PRUEBAS COMPLETADAS" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Revisa los resultados arriba para verificar el estado de cada prueba." -ForegroundColor White
Write-Host "Los endpoints con ✓ funcionaron correctamente." -ForegroundColor Green
Write-Host "Los endpoints con ✗ requieren atención." -ForegroundColor Red
Write-Host ""
