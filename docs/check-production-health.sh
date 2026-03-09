#!/bin/bash

# Script de Verificación Post-Migración
# Agroservicio Milagro de Dios

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuración
DB_CONTAINER="agroservicio-db"
PHP_CONTAINER="agroservicio-app"
DB_USER="root"
DB_NAME="agroservicio"

print_message() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

print_success() {
    echo -e "${GREEN}[✓] $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}[⚠] $1${NC}"
}

print_error() {
    echo -e "${RED}[✗] $1${NC}"
}

# Contador de errores
ERROR_COUNT=0

increment_error() {
    ((ERROR_COUNT++))
    print_error "$1"
}

# Verificar estado de contenedores
check_containers() {
    print_message "Verificando estado de contenedores Docker..."

    if docker ps | grep -q $PHP_CONTAINER; then
        print_success "Contenedor PHP está ejecutándose"
    else
        increment_error "Contenedor PHP no está ejecutándose"
    fi

    if docker ps | grep -q $DB_CONTAINER; then
        print_success "Contenedor MySQL está ejecutándose"
    else
        increment_error "Contenedor MySQL no está ejecutándose"
    fi
}

# Verificar migraciones
check_migrations() {
    print_message "Verificando estado de migraciones..."

    read -s -p "Contraseña de MySQL: " DB_PASSWORD
    echo

    # Verificar que las 3 nuevas migraciones están aplicadas
    EXPECTED_MIGRATIONS=(
        "2025_08_15_221307_add_sku_to_inventory_if_not_exists"
        "2025_08_15_230215_add_unique_constraints_to_providers_table"
        "2025_08_15_230510_add_unique_constraint_to_products_code"
    )

    for migration in "${EXPECTED_MIGRATIONS[@]}"; do
        if docker exec $PHP_CONTAINER php artisan migrate:status | grep -q "$migration"; then
            print_success "Migración aplicada: $migration"
        else
            increment_error "Migración faltante: $migration"
        fi
    done
}

# Verificar estructura de tablas
check_table_structures() {
    print_message "Verificando estructuras de tablas..."

    # Verificar tabla inventory
    print_message "Verificando tabla inventory..."
    INVENTORY_COLUMNS=$(docker exec $DB_CONTAINER mysql -u $DB_USER -p$DB_PASSWORD $DB_NAME -e "SHOW COLUMNS FROM inventory;" 2>/dev/null | grep -E "(sku|name|description|price|category|user_id|provider_id|active)")

    if echo "$INVENTORY_COLUMNS" | grep -q "sku"; then
        print_success "Columna 'sku' existe en inventory"
    else
        increment_error "Columna 'sku' faltante en inventory"
    fi

    if echo "$INVENTORY_COLUMNS" | grep -q "active"; then
        print_success "Columna 'active' existe en inventory"
    else
        increment_error "Columna 'active' faltante en inventory"
    fi
}

# Verificar índices únicos
check_unique_constraints() {
    print_message "Verificando restricciones únicas..."

    # Verificar índices en providers
    PROVIDER_INDEXES=$(docker exec $DB_CONTAINER mysql -u $DB_USER -p$DB_PASSWORD $DB_NAME -e "SHOW INDEX FROM providers WHERE Key_name LIKE '%unique%';" 2>/dev/null)

    if echo "$PROVIDER_INDEXES" | grep -q "providers_ncr_unique"; then
        print_success "Índice único NCR en providers"
    else
        increment_error "Índice único NCR faltante en providers"
    fi

    if echo "$PROVIDER_INDEXES" | grep -q "providers_nit_unique"; then
        print_success "Índice único NIT en providers"
    else
        increment_error "Índice único NIT faltante en providers"
    fi

    # Verificar índice en products
    PRODUCT_INDEXES=$(docker exec $DB_CONTAINER mysql -u $DB_USER -p$DB_PASSWORD $DB_NAME -e "SHOW INDEX FROM products WHERE Key_name LIKE '%unique%';" 2>/dev/null)

    if echo "$PRODUCT_INDEXES" | grep -q "products_code_unique"; then
        print_success "Índice único code en products"
    else
        increment_error "Índice único code faltante en products"
    fi
}

# Verificar archivos de la aplicación
check_application_files() {
    print_message "Verificando archivos de la aplicación..."

    # Verificar nuevos request files
    if [ -f "app/Http/Requests/ProviderRequest.php" ]; then
        print_success "Archivo ProviderRequest.php existe"
    else
        increment_error "Archivo ProviderRequest.php faltante"
    fi

    if [ -f "app/Http/Requests/ProviderUpdateRequest.php" ]; then
        print_success "Archivo ProviderUpdateRequest.php existe"
    else
        increment_error "Archivo ProviderUpdateRequest.php faltante"
    fi

    # Verificar migraciones
    for migration in "2025_08_15_221307_add_sku_to_inventory_if_not_exists.php" "2025_08_15_230215_add_unique_constraints_to_providers_table.php" "2025_08_15_230510_add_unique_constraint_to_products_code.php"; do
        if [ -f "database/migrations/$migration" ]; then
            print_success "Migración existe: $migration"
        else
            increment_error "Migración faltante: $migration"
        fi
    done
}

# Probar funcionalidades básicas
test_basic_functionality() {
    print_message "Probando funcionalidades básicas..."

    # Verificar que artisan funciona
    if docker exec $PHP_CONTAINER php artisan --version >/dev/null 2>&1; then
        print_success "Artisan funciona correctamente"
    else
        increment_error "Artisan no funciona"
    fi

    # Verificar cache
    if docker exec $PHP_CONTAINER php artisan config:show | head -1 >/dev/null 2>&1; then
        print_success "Sistema de configuración funciona"
    else
        increment_error "Sistema de configuración con problemas"
    fi
}

# Verificar logs por errores recientes
check_logs() {
    print_message "Verificando logs por errores recientes..."

    # Verificar logs de Laravel de los últimos 5 minutos
    RECENT_ERRORS=$(docker exec $PHP_CONTAINER find storage/logs -name "*.log" -mmin -5 -exec grep -l "ERROR\|CRITICAL\|EMERGENCY" {} \; 2>/dev/null | wc -l)

    if [ "$RECENT_ERRORS" -eq 0 ]; then
        print_success "No hay errores recientes en logs"
    else
        print_warning "Se encontraron $RECENT_ERRORS archivos de log con errores recientes"
        print_message "Revisar manualmente: storage/logs/"
    fi
}

# Pruebas de integridad de datos
test_data_integrity() {
    print_message "Probando integridad de datos..."

    # Contar registros en tablas principales
    USERS_COUNT=$(docker exec $DB_CONTAINER mysql -u $DB_USER -p$DB_PASSWORD $DB_NAME -e "SELECT COUNT(*) FROM users;" 2>/dev/null | tail -1)
    PRODUCTS_COUNT=$(docker exec $DB_CONTAINER mysql -u $DB_USER -p$DB_PASSWORD $DB_NAME -e "SELECT COUNT(*) FROM products;" 2>/dev/null | tail -1)
    PROVIDERS_COUNT=$(docker exec $DB_CONTAINER mysql -u $DB_USER -p$DB_PASSWORD $DB_NAME -e "SELECT COUNT(*) FROM providers;" 2>/dev/null | tail -1)
    INVENTORY_COUNT=$(docker exec $DB_CONTAINER mysql -u $DB_USER -p$DB_PASSWORD $DB_NAME -e "SELECT COUNT(*) FROM inventory;" 2>/dev/null | tail -1)

    print_success "Registros en usuarios: $USERS_COUNT"
    print_success "Registros en productos: $PRODUCTS_COUNT"
    print_success "Registros en proveedores: $PROVIDERS_COUNT"
    print_success "Registros en inventario: $INVENTORY_COUNT"

    # Verificar que no hay NULLs inesperados en campos requeridos
    NULL_USERS=$(docker exec $DB_CONTAINER mysql -u $DB_USER -p$DB_PASSWORD $DB_NAME -e "SELECT COUNT(*) FROM users WHERE email IS NULL;" 2>/dev/null | tail -1)
    NULL_PRODUCTS=$(docker exec $DB_CONTAINER mysql -u $DB_USER -p$DB_PASSWORD $DB_NAME -e "SELECT COUNT(*) FROM products WHERE name IS NULL;" 2>/dev/null | tail -1)

    if [ "$NULL_USERS" -eq 0 ]; then
        print_success "No hay usuarios con email NULL"
    else
        increment_error "Hay $NULL_USERS usuarios con email NULL"
    fi

    if [ "$NULL_PRODUCTS" -eq 0 ]; then
        print_success "No hay productos con nombre NULL"
    else
        increment_error "Hay $NULL_PRODUCTS productos con nombre NULL"
    fi
}

# Función principal
main() {
    print_message "=== VERIFICACIÓN POST-MIGRACIÓN ==="
    print_message "Fecha: $(date)"
    echo

    check_containers
    echo

    check_application_files
    echo

    check_migrations
    echo

    check_table_structures
    echo

    check_unique_constraints
    echo

    test_basic_functionality
    echo

    check_logs
    echo

    test_data_integrity
    echo

    # Resumen final
    print_message "=== RESUMEN DE VERIFICACIÓN ==="

    if [ $ERROR_COUNT -eq 0 ]; then
        print_success "¡MIGRACIÓN EXITOSA! No se encontraron errores."
        echo
        print_message "Pruebas manuales recomendadas:"
        echo "1. Crear un nuevo proveedor"
        echo "2. Editar un proveedor existente"
        echo "3. Crear un nuevo producto"
        echo "4. Verificar duplicados (intentar crear proveedor con mismo NCR/NIT)"
        echo "5. Verificar duplicados (intentar crear producto con mismo código)"
        echo "6. Revisar inventario y nuevos campos"
    else
        print_error "Se encontraron $ERROR_COUNT errores durante la verificación"
        print_warning "Revisar los errores antes de considerar la migración como exitosa"
        exit 1
    fi
}

# Ejecutar verificación
main
