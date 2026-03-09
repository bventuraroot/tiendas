#!/bin/bash

# Script para verificar qué tablas existen en ambos archivos
# Agroservicio Milagro de Dios

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_message() {
    echo -e "${BLUE}[INFO] $1${NC}"
}

print_success() {
    echo -e "${GREEN}[✓] $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}[!] $1${NC}"
}

print_error() {
    echo -e "${RED}[✗] $1${NC}"
}

# Configuración
LOCAL_FILE="Recursos /Agroservicio_Local-2025_08_18_10_44_44-dump.sql"
NUBE_FILE="Recursos /Agroservicio_Nube-2025_08_18_10_47_29-dump.sql"

# Función para verificar tablas
verify_tables() {
    print_message "=== VERIFICANDO EXISTENCIA DE TABLAS ==="
    echo
    
    # Lista de tablas a verificar
    TABLES_TO_CHECK=(
        "users"
        "password_resets"
        "failed_jobs"
        "personal_access_tokens"
        "countries"
        "departments"
        "municipalities"
        "economicactivities"
        "addresses"
        "phones"
        "companies"
        "clients"
        "providers"
        "products"
        "inventory"
        "sales"
        "salesdetails"
        "catlists"
        "catdetails"
        "permissions"
        "roles"
        "model_has_permissions"
        "model_has_roles"
        "role_has_permissions"
        "permission_company"
        "marcas"
        "migrations"
        "typedocuments"
        "iva"
        "ambientes"
        "dte"
        "config"
        "credits"
        "ai_conversations"
        "ai_chat_settings"
        "purchase_details"
        "quotations"
        "quotation_details"
    )
    
    echo "Tabla                    | Local | Nube  | Datos"
    echo "-------------------------|-------|-------|-------"
    
    VALID_TABLES=()
    TABLES_WITH_DATA=()
    
    for table in "${TABLES_TO_CHECK[@]}"; do
        # Verificar si existe CREATE TABLE en local
        if grep -q "CREATE TABLE.*\`$table\`" "$LOCAL_FILE"; then
            LOCAL_STATUS="✓"
        else
            LOCAL_STATUS="✗"
        fi
        
        # Verificar si existe INSERT en nube
        if grep -q "INSERT INTO.*\`$table\`" "$NUBE_FILE"; then
            NUBE_STATUS="✓"
            TABLES_WITH_DATA+=("$table")
        else
            NUBE_STATUS="✗"
        fi
        
        # Si existe en local, es válida para migración
        if [ "$LOCAL_STATUS" = "✓" ]; then
            VALID_TABLES+=("$table")
        fi
        
        printf "%-24s | %-5s | %-5s | %s\n" "$table" "$LOCAL_STATUS" "$NUBE_STATUS" "$NUBE_STATUS"
    done
    
    echo
    print_message "=== RESUMEN ==="
    echo "Tablas válidas para migración: ${#VALID_TABLES[@]}"
    echo "Tablas con datos en nube: ${#TABLES_WITH_DATA[@]}"
    echo
    
    print_message "=== TABLAS VÁLIDAS PARA MIGRACIÓN ==="
    for table in "${VALID_TABLES[@]}"; do
        print_success "$table"
    done
    
    echo
    print_message "=== TABLAS CON DATOS EN NUBE ==="
    for table in "${TABLES_WITH_DATA[@]}"; do
        print_success "$table"
    done
    
    # Generar array para script
    echo
    print_message "=== ARRAY PARA SCRIPT DE MIGRACIÓN ==="
    echo "VALID_TABLES=("
    for table in "${VALID_TABLES[@]}"; do
        echo "    \"$table\""
    done
    echo ")"
}

# Función principal
main() {
    if [ ! -f "$LOCAL_FILE" ]; then
        print_error "Archivo local no encontrado: $LOCAL_FILE"
        exit 1
    fi
    
    if [ ! -f "$NUBE_FILE" ]; then
        print_error "Archivo de nube no encontrado: $NUBE_FILE"
        exit 1
    fi
    
    verify_tables
}

# Ejecutar función principal
main
