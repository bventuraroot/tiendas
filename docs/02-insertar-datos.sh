#!/bin/bash

# Script 2: Insertar SOLO los datos (después de crear estructura)
# Maneja específicamente la tabla products
# Agroservicio Milagro de Dios - DATOS

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_message() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

print_success() {
    echo -e "${GREEN}[SUCCESS] $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}[WARNING] $1${NC}"
}

print_error() {
    echo -e "${RED}[ERROR] $1${NC}"
}

# Configuración
NUBE_FILE="Recursos /Agroservicio_Nube-2025_08_18_10_47_29-dump.sql"
OUTPUT_FILE="Recursos /02_DATOS_$(date +%Y%m%d_%H%M%S).sql"
TEMP_DIR="./temp_datos"
DB_NAME="agroserviciomila_agroserviciomilagro"

# Crear directorio temporal
mkdir -p $TEMP_DIR

# Lista de tablas que tienen datos en la nube (según verificación anterior)
TABLES_WITH_DATA=(
    "users"
    "password_resets"
    "countries"
    "departments"
    "municipalities"
    "economicactivities"
    "addresses"
    "phones"
    "companies"
    "clients"
    "providers"
    "sales"
    "salesdetails"
    "catlists"
    "catdetails"
    "permissions"
    "roles"
    "model_has_roles"
    "role_has_permissions"
    "permission_company"
    "marcas"
    "migrations"
    "typedocuments"
    "ambientes"
    "aerolineas"
    "aeropuertos"
    "docs"
    "proveedores"
    "products"
)

# Función para extraer INSERT normal
extract_normal_insert() {
    local table_name=$1
    local source_file=$2
    local output_file=$3

    if grep -q "INSERT INTO.*\`$table_name\`" "$source_file"; then
        print_message "Extrayendo datos de: $table_name"
        echo "-- Datos para tabla: $table_name" >> "$output_file"
        grep "INSERT INTO.*\`$table_name\`" "$source_file" >> "$output_file" 2>/dev/null
        echo "" >> "$output_file"
        return 0
    else
        return 1
    fi
}

# Función especial para convertir datos de products
convert_products_insert() {
    local source_file=$1
    local output_file=$2

    print_message "Convirtiendo datos de products para compatibilidad total..."

    if grep -q "INSERT INTO.*\`products\`" "$source_file"; then

        echo "-- =====================================================" >> "$output_file"
        echo "-- DATOS DE PRODUCTS (CONVERTIDOS PARA COMPATIBILIDAD)" >> "$output_file"
        echo "-- Estructura nube: 15 campos → Estructura local: 19 campos" >> "$output_file"
        echo "-- Campos que se agregarán automáticamente con valores por defecto:" >> "$output_file"
        echo "-- - has_expiration: 0 (por defecto)" >> "$output_file"
        echo "-- - expiration_days: NULL" >> "$output_file"
        echo "-- - expiration_type: 'days' (por defecto)" >> "$output_file"
        echo "-- - expiration_notes: NULL" >> "$output_file"
        echo "-- =====================================================" >> "$output_file"

        # Convertir INSERT con nombres de columnas específicos
        echo "INSERT INTO \`products\` (\`id\`, \`code\`, \`name\`, \`state\`, \`cfiscal\`, \`type\`, \`price\`, \`description\`, \`image\`, \`provider_id\`, \`user_id\`, \`created_at\`, \`updated_at\`, \`marca_id\`, \`category\`) VALUES" >> "$output_file"

        # Extraer solo los valores, quitando el INSERT INTO original
        grep "INSERT INTO.*\`products\`" "$source_file" | \
        sed 's/INSERT INTO `products` VALUES //' >> "$output_file"

        echo "" >> "$output_file"
        print_success "Datos de products convertidos para compatibilidad total"
    else
        print_warning "No se encontraron datos de products"
    fi
}

# Función principal para crear archivo de datos
create_data_file() {
    print_message "Creando archivo de datos..."

    # Crear archivo de datos
    cat > "$OUTPUT_FILE" << EOF
-- =====================================================
-- DATOS DE LA NUBE - AGROSERVICIO MILAGRO DE DIOS
-- Solo INSERT statements (ejecutar DESPUÉS de crear estructura)
-- Generado: $(date)
-- Base de datos: $DB_NAME
-- =====================================================

-- Configuración inicial
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Usar la base de datos correcta
USE \`$DB_NAME\`;

-- =====================================================
-- DATOS DE LA NUBE
-- =====================================================

EOF

    # Crear archivo temporal para datos
    > "$TEMP_DIR/data.sql"

    # Contador
    TABLES_WITH_DATA_COUNT=0

    # Procesar cada tabla que tiene datos (EXCEPTO products)
    for table in "${TABLES_WITH_DATA[@]}"; do
        if [ "$table" != "products" ]; then
            if extract_normal_insert "$table" "$NUBE_FILE" "$TEMP_DIR/data.sql"; then
                ((TABLES_WITH_DATA_COUNT++))
            fi
        fi
    done

    # Agregar datos normales al archivo final
    cat "$TEMP_DIR/data.sql" >> "$OUTPUT_FILE"

    # Manejar products por separado
    convert_products_insert "$NUBE_FILE" "$OUTPUT_FILE"
    ((TABLES_WITH_DATA_COUNT++))

    # Agregar finalización
    cat >> "$OUTPUT_FILE" << EOF

-- =====================================================
-- FINALIZAR TRANSACCIÓN
-- =====================================================
COMMIT;

-- =====================================================
-- INSTRUCCIONES
-- =====================================================
-- 1. Este archivo debe ejecutarse DESPUÉS del archivo de estructura
-- 2. Contiene todos los datos de la nube
-- 3. Los datos de products están convertidos para compatibilidad
-- =====================================================
EOF

    print_success "Archivo de datos creado: $OUTPUT_FILE"
    print_success "Tablas con datos: $TABLES_WITH_DATA_COUNT"
}

# Función para verificar archivos
verify_files() {
    print_message "Verificando archivos de entrada..."

    if [ ! -f "$NUBE_FILE" ]; then
        print_error "Archivo de nube no encontrado: $NUBE_FILE"
        exit 1
    fi

    NUBE_SIZE=$(du -h "$NUBE_FILE" | cut -f1)

    print_success "Archivo nube: $NUBE_FILE ($NUBE_SIZE)"
    print_success "Base de datos objetivo: $DB_NAME"
}

# Función para mostrar estadísticas
show_statistics() {
    print_message "Estadísticas del archivo generado:"

    if [ -f "$OUTPUT_FILE" ]; then
        OUTPUT_SIZE=$(du -h "$OUTPUT_FILE" | cut -f1)
        TOTAL_LINES=$(wc -l < "$OUTPUT_FILE")
        INSERT_COUNT=$(grep -c "INSERT INTO" "$OUTPUT_FILE" || echo "0")

        echo "  📁 Archivo generado: $OUTPUT_FILE"
        echo "  📊 Tamaño: $OUTPUT_SIZE"
        echo "  📈 Total de líneas: $TOTAL_LINES"
        echo "  📝 Inserts: $INSERT_COUNT"
        echo "  🗄️  Base de datos: $DB_NAME"

        # Verificar products
        if grep -q "INSERT INTO.*\`products\`.*\`id\`.*\`code\`" "$OUTPUT_FILE"; then
            print_success "✅ Datos de products convertidos con nombres de columnas"
        fi
    fi
}

# Función para limpiar archivos temporales
cleanup() {
    print_message "Limpiando archivos temporales..."
    rm -rf "$TEMP_DIR"
    print_success "Limpieza completada"
}

# Función principal
main() {
    print_message "=== CREANDO ARCHIVO DE DATOS ==="
    print_message "Solo INSERT statements (para ejecutar después de estructura)"
    print_message "Base de datos objetivo: $DB_NAME"
    echo

    # Verificar archivos
    verify_files
    echo

    # Crear archivo de datos
    create_data_file
    echo

    # Mostrar estadísticas
    show_statistics
    echo

    # Limpiar archivos temporales
    cleanup
    echo

    print_success "=== ARCHIVO DE DATOS CREADO EXITOSAMENTE ==="
    print_message "Ubicación: $OUTPUT_FILE"
    echo
    print_warning "INSTRUCCIONES DE USO:"
    echo "1. Importar PRIMERO el archivo 01_ESTRUCTURA_*.sql"
    echo "2. Importar DESPUÉS este archivo de datos"
    echo "3. Los datos de products están convertidos para compatibilidad"
}

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    print_error "Este script debe ejecutarse desde el directorio raíz del proyecto Laravel"
    exit 1
fi

# Ejecutar función principal
main
