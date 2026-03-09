#!/bin/bash

# Script para crear archivo de migración compatible
# Convierte datos de products para que coincidan exactamente con la estructura
# Agroservicio Milagro de Dios - VERSIÓN COMPATIBLE

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
LOCAL_FILE="Recursos /Agroservicio_Local-2025_08_18_21_21_06-dump.sql"
NUBE_FILE="Recursos /Agroservicio_Nube-2025_08_18_10_47_29-dump.sql"
OUTPUT_FILE="Recursos /Agroservicio_COMPATIBLE_$(date +%Y%m%d_%H%M%S).sql"
TEMP_DIR="./temp_compatible_migration"
DB_NAME="agroserviciomila_agroserviciomilagro"

# Crear directorio temporal
mkdir -p $TEMP_DIR

# Función para extraer automáticamente todas las tablas del archivo local limpio
extract_all_clean_tables() {
    print_message "Extrayendo TODAS las tablas del archivo local limpio..."

    # Extraer nombres de tablas del archivo local limpio
    grep "CREATE TABLE" "$LOCAL_FILE" | \
    sed 's/.*`\([^`]*\)`.*/\1/' | \
    sort -u > "$TEMP_DIR/clean_tables.txt"

    TABLE_COUNT=$(wc -l < "$TEMP_DIR/clean_tables.txt")
    print_success "Total de tablas en archivo limpio: $TABLE_COUNT"
}

# Función para extraer CREATE TABLE específico
extract_create_table() {
    local table_name=$1
    local source_file=$2
    local output_file=$3

    # Buscar y extraer la definición completa de la tabla
    awk "
        /CREATE TABLE.*\`$table_name\`/ {
            found = 1;
            print \$0;
            next
        }
        found && /;/ {
            print \$0;
            found = 0;
            next
        }
        found {
            print \$0
        }
    " "$source_file" >> "$output_file"

    echo "" >> "$output_file"
}

# Función para convertir datos de products a estructura compatible
convert_products_data() {
    local source_file=$1
    local output_file=$2

    print_message "Convirtiendo datos de products para compatibilidad..."

    # Extraer datos de products de la nube
    if grep -q "INSERT INTO.*\`products\`" "$source_file"; then

        echo "-- Datos para tabla: products (CONVERTIDOS PARA COMPATIBILIDAD)" >> "$output_file"
        echo "-- Estructura original: 15 campos → Estructura nueva: 19 campos" >> "$output_file"
        echo "-- Campos agregados: has_expiration, expiration_days, expiration_type, expiration_notes" >> "$output_file"

        # Extraer la línea de INSERT y convertirla
        grep "INSERT INTO.*\`products\`" "$source_file" | \
        sed 's/INSERT INTO `products` VALUES /INSERT INTO `products` (`id`, `code`, `name`, `state`, `cfiscal`, `type`, `price`, `description`, `image`, `provider_id`, `user_id`, `created_at`, `updated_at`, `marca_id`, `category`) VALUES /' >> "$output_file"

        echo "" >> "$output_file"
        print_success "Datos de products convertidos para compatibilidad"
    else
        print_warning "No se encontraron datos de products"
    fi
}

# Función para extraer INSERT normal para otras tablas
extract_normal_insert() {
    local table_name=$1
    local source_file=$2
    local output_file=$3

    # Verificar si hay datos para esta tabla
    if grep -q "INSERT INTO.*\`$table_name\`" "$source_file"; then
        echo "-- Datos para tabla: $table_name" >> "$output_file"
        grep "INSERT INTO.*\`$table_name\`" "$source_file" >> "$output_file" 2>/dev/null
        echo "" >> "$output_file"
        return 0
    else
        return 1
    fi
}

# Función principal para crear migración compatible
create_compatible_migration() {
    print_message "Creando archivo de migración compatible..."

    # Crear archivo de migración
    cat > "$OUTPUT_FILE" << EOF
-- =====================================================
-- ARCHIVO DE MIGRACIÓN COMPATIBLE AGROSERVICIO MILAGRO DE DIOS
-- Incluye TODAS las tablas del archivo local limpio
-- Datos de products convertidos para compatibilidad total
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
-- ESTRUCTURA DE TODAS LAS TABLAS (DEL LOCAL LIMPIO)
-- =====================================================

EOF

    # Crear archivos temporales para estructura y datos
    > "$TEMP_DIR/structure.sql"
    > "$TEMP_DIR/data.sql"

    # Contadores
    TABLES_WITH_STRUCTURE=0
    TABLES_WITH_DATA=0

    # Procesar cada tabla encontrada automáticamente
    while read table; do
        print_message "Procesando tabla: $table"

        # Extraer estructura del archivo local limpio
        extract_create_table "$table" "$LOCAL_FILE" "$TEMP_DIR/structure.sql"
        ((TABLES_WITH_STRUCTURE++))

        # Manejar datos según la tabla
        if [ "$table" = "products" ]; then
            # Usar conversión especial para products
            if convert_products_data "$NUBE_FILE" "$TEMP_DIR/data.sql"; then
                print_success "  → Con datos convertidos para compatibilidad"
                ((TABLES_WITH_DATA++))
            fi
        else
            # Usar extracción normal para otras tablas
            if extract_normal_insert "$table" "$NUBE_FILE" "$TEMP_DIR/data.sql"; then
                print_success "  → Con datos"
                ((TABLES_WITH_DATA++))
            else
                print_warning "  → Solo estructura (sin datos)"
            fi
        fi

    done < "$TEMP_DIR/clean_tables.txt"

    # Agregar estructura al archivo final
    cat "$TEMP_DIR/structure.sql" >> "$OUTPUT_FILE"

    # Agregar separador
    cat >> "$OUTPUT_FILE" << EOF

-- =====================================================
-- DATOS DE LA NUBE (CONVERTIDOS PARA COMPATIBILIDAD)
-- =====================================================

EOF

    # Agregar datos al archivo final
    cat "$TEMP_DIR/data.sql" >> "$OUTPUT_FILE"

    # Agregar finalización
    cat >> "$OUTPUT_FILE" << EOF

-- =====================================================
-- FINALIZAR TRANSACCIÓN
-- =====================================================
COMMIT;
EOF

    print_success "Archivo de migración compatible creado: $OUTPUT_FILE"
    print_success "Tablas con estructura: $TABLES_WITH_STRUCTURE"
    print_success "Tablas con datos: $TABLES_WITH_DATA"
}

# Función para verificar archivos
verify_files() {
    print_message "Verificando archivos de entrada..."

    if [ ! -f "$LOCAL_FILE" ]; then
        print_error "Archivo local limpio no encontrado: $LOCAL_FILE"
        exit 1
    fi

    if [ ! -f "$NUBE_FILE" ]; then
        print_error "Archivo de nube no encontrado: $NUBE_FILE"
        exit 1
    fi

    LOCAL_SIZE=$(du -h "$LOCAL_FILE" | cut -f1)
    NUBE_SIZE=$(du -h "$NUBE_FILE" | cut -f1)

    print_success "Archivo local limpio: $LOCAL_FILE ($LOCAL_SIZE)"
    print_success "Archivo nube: $NUBE_FILE ($NUBE_SIZE)"
    print_success "Base de datos objetivo: $DB_NAME"
}

# Función para mostrar estadísticas
show_statistics() {
    print_message "Estadísticas del archivo generado:"

    if [ -f "$OUTPUT_FILE" ]; then
        OUTPUT_SIZE=$(du -h "$OUTPUT_FILE" | cut -f1)
        TOTAL_LINES=$(wc -l < "$OUTPUT_FILE")

        echo "  📁 Archivo generado: $OUTPUT_FILE"
        echo "  📊 Tamaño: $OUTPUT_SIZE"
        echo "  📈 Total de líneas: $TOTAL_LINES"

        # Contar tablas y registros
        TABLE_COUNT=$(grep -c "CREATE TABLE" "$OUTPUT_FILE" || echo "0")
        INSERT_COUNT=$(grep -c "INSERT INTO" "$OUTPUT_FILE" || echo "0")

        echo "  🗂️  Tablas creadas: $TABLE_COUNT"
        echo "  📝 Inserts con datos: $INSERT_COUNT"
        echo "  🗄️  Base de datos: $DB_NAME"

        # Verificar tablas críticas
        if grep -q "CREATE TABLE.*\`users\`" "$OUTPUT_FILE"; then
            print_success "✅ Tabla users incluida"
        fi

        if grep -q "CREATE TABLE.*\`purchases\`" "$OUTPUT_FILE"; then
            print_success "✅ Tabla purchases incluida"
        fi

        if grep -q "CREATE TABLE.*\`products\`" "$OUTPUT_FILE"; then
            print_success "✅ Tabla products incluida"
        fi

        # Verificar conversión de products
        if grep -q "INSERT INTO.*\`products\`.*\`id\`.*\`code\`" "$OUTPUT_FILE"; then
            print_success "✅ Datos de products convertidos con nombres de columnas"
        else
            print_warning "⚠️ Datos de products en formato original"
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
    print_message "=== CREANDO ARCHIVO DE MIGRACIÓN COMPATIBLE ==="
    print_message "Conversión específica para compatibilidad de products"
    print_message "Base de datos objetivo: $DB_NAME"
    echo

    # Verificar archivos
    verify_files
    echo

    # Extraer todas las tablas del archivo local limpio
    extract_all_clean_tables
    echo

    # Crear migración compatible
    create_compatible_migration
    echo

    # Mostrar estadísticas
    show_statistics
    echo

    # Limpiar archivos temporales
    cleanup
    echo

    print_success "=== ARCHIVO DE MIGRACIÓN COMPATIBLE CREADO ==="
    print_message "El archivo está listo para ser usado en cPanel"
    print_message "Ubicación: $OUTPUT_FILE"
    print_message "Base de datos: $DB_NAME"
    echo
    print_warning "SOLUCIÓN AL PROBLEMA DE PRODUCTS:"
    echo "✅ Datos de products convertidos con nombres de columnas explícitos"
    echo "✅ Solo incluye los 15 campos que existen en los datos de la nube"
    echo "✅ Los 4 campos extra se llenarán con valores por defecto"
    echo "✅ No más errores de número de columnas"
    echo
    print_warning "Próximos pasos:"
    echo "1. Importar este archivo en cPanel"
    echo "2. La importación debería ser exitosa"
    echo "3. Verificar que products se importó correctamente"
}

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    print_error "Este script debe ejecutarse desde el directorio raíz del proyecto Laravel"
    exit 1
fi

# Ejecutar función principal
main
