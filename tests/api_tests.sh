#!/bin/bash

# Script de pruebas automatizadas para el Sistema UNITEPC
# Asegúrate de tener curl instalado y el servidor corriendo

BASE_URL="http://localhost/backend-inventarios/public/api"
TOKEN=""

# Colores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "========================================="
echo "UNITEPC - Sistema de Gestión de Activos"
echo "Script de Pruebas Automatizadas"
echo "========================================="
echo ""

# Función para imprimir resultados
print_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓ $2${NC}"
    else
        echo -e "${RED}✗ $2${NC}"
    fi
}

# Función para hacer peticiones
make_request() {
    local method=$1
    local endpoint=$2
    local data=$3
    local description=$4
    
    echo -e "${YELLOW}Probando: $description${NC}"
    
    if [ -z "$data" ]; then
        if [ -z "$TOKEN" ]; then
            response=$(curl -s -w "\n%{http_code}" -X $method "$BASE_URL$endpoint" \
                -H "Content-Type: application/json")
        else
            response=$(curl -s -w "\n%{http_code}" -X $method "$BASE_URL$endpoint" \
                -H "Content-Type: application/json" \
                -H "Authorization: Bearer $TOKEN")
        fi
    else
        if [ -z "$TOKEN" ]; then
            response=$(curl -s -w "\n%{http_code}" -X $method "$BASE_URL$endpoint" \
                -H "Content-Type: application/json" \
                -d "$data")
        else
            response=$(curl -s -w "\n%{http_code}" -X $method "$BASE_URL$endpoint" \
                -H "Content-Type: application/json" \
                -H "Authorization: Bearer $TOKEN" \
                -d "$data")
        fi
    fi
    
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | sed '$d')
    
    if [ $http_code -ge 200 ] && [ $http_code -lt 300 ]; then
        print_result 0 "$description - HTTP $http_code"
        echo "$body" | jq '.' 2>/dev/null || echo "$body"
    else
        print_result 1 "$description - HTTP $http_code"
        echo "$body"
    fi
    
    echo ""
    echo "$body"
}

# 1. AUTENTICACIÓN
echo "========================================="
echo "1. PRUEBAS DE AUTENTICACIÓN"
echo "========================================="
echo ""

login_response=$(curl -s -X POST "$BASE_URL/login" \
    -H "Content-Type: application/json" \
    -d '{
        "usuario": "admin",
        "password": "admin123"
    }')

TOKEN=$(echo $login_response | jq -r '.token' 2>/dev/null)

if [ ! -z "$TOKEN" ] && [ "$TOKEN" != "null" ]; then
    print_result 0 "Login exitoso - Token obtenido"
    echo "Token: ${TOKEN:0:20}..."
else
    print_result 1 "Login falló"
    echo "Response: $login_response"
    exit 1
fi

echo ""

# Verificar usuario actual
make_request "GET" "/me" "" "Obtener usuario actual"

# 2. PROVEEDORES
echo "========================================="
echo "2. PRUEBAS DE PROVEEDORES"
echo "========================================="
echo ""

make_request "GET" "/proveedores" "" "Listar proveedores"

make_request "POST" "/proveedores" '{
    "nombre": "Proveedor Test Bash",
    "nit": "9999999999",
    "telefono": "+591 70000000",
    "email": "test@bash.com",
    "direccion": "Av. Test #999",
    "activo": true
}' "Crear nuevo proveedor"

# 3. ITEMS
echo "========================================="
echo "3. PRUEBAS DE ITEMS"
echo "========================================="
echo ""

make_request "GET" "/items" "" "Listar items"

make_request "GET" "/inventario/bajo-stock" "" "Items bajo stock mínimo"

# 4. SOLICITUDES
echo "========================================="
echo "4. PRUEBAS DE SOLICITUDES"
echo "========================================="
echo ""

make_request "GET" "/solicitudes" "" "Listar solicitudes"

solicitud_response=$(curl -s -X POST "$BASE_URL/solicitudes" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $TOKEN" \
    -d '{
        "laboratorio_id": 1,
        "fecha_necesidad": "2025-12-20",
        "justificacion": "Prueba automatizada",
        "items": [
            {
                "item_id": 1,
                "cantidad_solicitada": 3,
                "unidad_medida": "L"
            }
        ]
    }')

SOLICITUD_ID=$(echo $solicitud_response | jq -r '.data.id' 2>/dev/null)

if [ ! -z "$SOLICITUD_ID" ] && [ "$SOLICITUD_ID" != "null" ]; then
    print_result 0 "Solicitud creada - ID: $SOLICITUD_ID"
    
    # Aprobar en nivel 1
    make_request "POST" "/solicitudes/$SOLICITUD_ID/aprobar-subalmacen" "" "Aprobar solicitud - Subalmacén"
else
    print_result 1 "Error al crear solicitud"
fi

# 5. ÓRDENES DE COMPRA
echo "========================================="
echo "5. PRUEBAS DE ÓRDENES DE COMPRA"
echo "========================================="
echo ""

make_request "GET" "/ordenes-compra" "" "Listar órdenes de compra"

orden_response=$(curl -s -X POST "$BASE_URL/ordenes-compra" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $TOKEN" \
    -d '{
        "proveedor_id": 1,
        "fecha_emision": "2025-11-29",
        "fecha_entrega_estimada": "2025-12-15",
        "condiciones_pago": "Pago contra entrega",
        "items": [
            {
                "item_id": 1,
                "cantidad_solicitada": 15,
                "unidad_medida": "L",
                "precio_unitario": 85.00
            }
        ]
    }')

ORDEN_ID=$(echo $orden_response | jq -r '.data.id' 2>/dev/null)

if [ ! -z "$ORDEN_ID" ] && [ "$ORDEN_ID" != "null" ]; then
    print_result 0 "Orden de compra creada - ID: $ORDEN_ID"
    
    # Aprobar orden
    make_request "POST" "/ordenes-compra/$ORDEN_ID/aprobar" "" "Aprobar orden de compra"
    
    # Confirmar orden
    make_request "POST" "/ordenes-compra/$ORDEN_ID/confirmar" "" "Confirmar orden de compra"
    
    # Listar pendientes de recepción
    make_request "GET" "/ordenes-compra/pendientes/recepcion" "" "Órdenes pendientes de recepción"
else
    print_result 1 "Error al crear orden de compra"
fi

# 6. INVENTARIO
echo "========================================="
echo "6. PRUEBAS DE INVENTARIO"
echo "========================================="
echo ""

make_request "GET" "/inventario/stock-detallado" "" "Stock detallado"

make_request "GET" "/inventario/por-categoria" "" "Stock por categoría"

make_request "GET" "/inventario/valoracion" "" "Valoración de inventario"

make_request "GET" "/inventario/kardex/1" "" "Kardex del item 1"

# 7. MOVIMIENTOS DE INVENTARIO
echo "========================================="
echo "7. PRUEBAS DE MOVIMIENTOS"
echo "========================================="
echo ""

make_request "GET" "/movimientos-inventario" "" "Listar movimientos"

# 8. PRÉSTAMOS
echo "========================================="
echo "8. PRUEBAS DE PRÉSTAMOS"
echo "========================================="
echo ""

make_request "GET" "/prestamos" "" "Listar préstamos"

make_request "GET" "/prestamos/activos/lista" "" "Préstamos activos"

make_request "GET" "/prestamos/vencidos/lista" "" "Préstamos vencidos"

# RESUMEN
echo "========================================="
echo "PRUEBAS COMPLETADAS"
echo "========================================="
echo ""
echo "Revisa los resultados arriba para verificar el estado de cada prueba."
echo "Los endpoints con ✓ funcionaron correctamente."
echo "Los endpoints con ✗ requieren atención."
echo ""
