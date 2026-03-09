#!/bin/bash

# Script 1: Crear SOLO la estructura de todas las tablas
# Agroservicio Milagro de Dios - ESTRUCTURA

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
OUTPUT_FILE="Recursos /01_ESTRUCTURA_$(date +%Y%m%d_%H%M%S).sql"
TEMP_DIR="./temp_estructura"
DB_NAME="agroserviciomila_agroserviciomilagro"

# Crear directorio temporal
mkdir -p $TEMP_DIR

# Función para extraer automáticamente todas las tablas
extract_all_tables() {
    print_message "Extrayendo TODAS las tablas del archivo local limpio..."

    # Extraer nombres de tablas
    grep "CREATE TABLE" "$LOCAL_FILE" | \
    sed 's/.*`\([^`]*\)`.*/\1/' | \
    sort -u > "$TEMP_DIR/all_tables.txt"

    # Mostrar tablas encontradas
    print_message "Tablas encontradas:"
    cat "$TEMP_DIR/all_tables.txt" | while read table; do
        echo "  ✓ $table"
    done

    TABLE_COUNT=$(wc -l < "$TEMP_DIR/all_tables.txt")
    print_success "Total de tablas: $TABLE_COUNT"
}

# Función para extraer CREATE TABLE específico
extract_create_table() {
    local table_name=$1
    local source_file=$2
    local output_file=$3

    print_message "Extrayendo estructura de: $table_name"

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

# Función principal para crear archivo de estructura
create_structure_file() {
    print_message "Creando archivo de estructura..."

    # Crear archivo de estructura
    cat > "$OUTPUT_FILE" << EOF
-- =====================================================
-- ESTRUCTURA DE TABLAS - AGROSERVICIO MILAGRO DE DIOS
-- Solo CREATE TABLE (sin datos)
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
-- ESTRUCTURA DE TODAS LAS TABLAS
-- =====================================================

EOF

    # Crear archivo temporal para estructura
    > "$TEMP_DIR/structure.sql"

    # Contador
    TABLES_CREATED=0

    # Procesar cada tabla
    while read table; do
        extract_create_table "$table" "$LOCAL_FILE" "$TEMP_DIR/structure.sql"
        ((TABLES_CREATED++))
    done < "$TEMP_DIR/all_tables.txt"

    # Agregar estructura al archivo final
    cat "$TEMP_DIR/structure.sql" >> "$OUTPUT_FILE"

    # Agregar finalización
    cat >> "$OUTPUT_FILE" << EOF

-- =====================================================
-- FINALIZAR TRANSACCIÓN
-- =====================================================
COMMIT;

-- =====================================================
-- INSTRUCCIONES
-- =====================================================
-- 1. Importa este archivo PRIMERO para crear todas las tablas
-- 2. Después ejecuta el archivo 02-insertar-datos.sql para agregar los datos
-- =====================================================
EOF

    print_success "Archivo de estructura creado: $OUTPUT_FILE"
    print_success "Tablas creadas: $TABLES_CREATED"
}

# Función para verificar archivos
verify_files() {
    print_message "Verificando archivos de entrada..."

    if [ ! -f "$LOCAL_FILE" ]; then
        print_error "Archivo local no encontrado: $LOCAL_FILE"
        exit 1
    fi

    LOCAL_SIZE=$(du -h "$LOCAL_FILE" | cut -f1)

    print_success "Archivo local: $LOCAL_FILE ($LOCAL_SIZE)"
    print_success "Base de datos objetivo: $DB_NAME"
}

# Función para mostrar estadísticas
show_statistics() {
    print_message "Estadísticas del archivo generado:"

    if [ -f "$OUTPUT_FILE" ]; then
        OUTPUT_SIZE=$(du -h "$OUTPUT_FILE" | cut -f1)
        TOTAL_LINES=$(wc -l < "$OUTPUT_FILE")
        TABLE_COUNT=$(grep -c "CREATE TABLE" "$OUTPUT_FILE" || echo "0")

        echo "  📁 Archivo generado: $OUTPUT_FILE"
        echo "  📊 Tamaño: $OUTPUT_SIZE"
        echo "  📈 Total de líneas: $TOTAL_LINES"
        echo "  🗂️  Tablas creadas: $TABLE_COUNT"
        echo "  🗄️  Base de datos: $DB_NAME"

        # Verificar tablas críticas
        if grep -q "CREATE TABLE.*\`users\`" "$OUTPUT_FILE"; then
            print_success "✅ Estructura users incluida"
        fi

        if grep -q "CREATE TABLE.*\`purchases\`" "$OUTPUT_FILE"; then
            print_success "✅ Estructura purchases incluida"
        fi

        if grep -q "CREATE TABLE.*\`products\`" "$OUTPUT_FILE"; then
            print_success "✅ Estructura products incluida"
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
    print_message "=== CREANDO ARCHIVO DE ESTRUCTURA DE TABLAS ==="
    print_message "Solo CREATE TABLE (sin datos)"
    print_message "Base de datos objetivo: $DB_NAME"
    echo

    # Verificar archivos
    verify_files
    echo

    # Extraer todas las tablas
    extract_all_tables
    echo

    # Crear archivo de estructura
    create_structure_file
    echo

    # Mostrar estadísticas
    show_statistics
    echo

    # Limpiar archivos temporales
    cleanup
    echo

    print_success "=== ARCHIVO DE ESTRUCTURA CREADO EXITOSAMENTE ==="
    print_message "Ubicación: $OUTPUT_FILE"
    echo
    print_warning "PRÓXIMO PASO:"
    echo "1. Importar ESTE archivo primero en cPanel"
    echo "2. Después ejecutar el script 02-insertar-datos.sh"
}

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    print_error "Este script debe ejecutarse desde el directorio raíz del proyecto Laravel"
    exit 1
fi

# Ejecutar función principal
main
