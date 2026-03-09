# Documentación: Registro de Productos

## 📋 Índice
1. [Orden de Creación](#orden-de-creación)
2. [Crear Marcas](#1-crear-marcas)
3. [Crear Proveedores](#2-crear-proveedores)
4. [Crear Laboratorios Farmacéuticos](#3-crear-laboratorios-farmacéuticos)
5. [Registrar Productos](#4-registrar-productos)
6. [Campos del Producto](#campos-del-producto)
7. [Rutas y Accesos](#rutas-y-accesos)

---

## Orden de Creación

Para registrar un producto correctamente, es necesario crear primero los siguientes elementos en este orden:

1. **Marcas** (Obligatorio - tiene valor por defecto si no se selecciona)
2. **Proveedores** (Obligatorio - tiene valor por defecto si no se selecciona)
3. **Laboratorios Farmacéuticos** (Opcional)
4. **Productos** (Requiere marcas y proveedores)

> **Nota:** Aunque las marcas y proveedores tienen valores por defecto (ID = 1), es recomendable crear los registros reales antes de registrar productos para mantener la integridad de los datos.

---

## 1. Crear Marcas

### Descripción
Las marcas identifican el fabricante o distribuidor del producto.

### Acceso
- **Ruta:** `/marcas/index`
- **Controlador:** `MarcaController@index`
- **Vista:** `resources/views/marcas/index.blade.php`

### Campos Requeridos

| Campo | Tipo | Descripción | Requerido |
|-------|------|-------------|-----------|
| `name` | string | Nombre de la marca | ✅ Sí |
| `description` | string | Descripción adicional | ❌ No |

### Proceso de Creación

1. Acceder a la página de marcas: `/marcas/index`
2. Hacer clic en el botón "Agregar Marca" o similar
3. Completar el formulario:
   - **Nombre Marca:** Ejemplo: "Bayer", "Pfizer", "Roche"
   - **Descripción:** Información adicional (opcional)
4. Guardar el formulario

### Ejemplo de Datos

```php
Marca::create([
    'name' => 'Bayer',
    'description' => 'Laboratorio farmacéutico alemán',
    'user_id' => auth()->id()
]);
```

### Estructura de la Base de Datos

```sql
CREATE TABLE marcas (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description VARCHAR(255) NULL,
    image VARCHAR(255) NULL,
    status VARCHAR(255) DEFAULT 'active',
    user_id BIGINT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## 2. Crear Proveedores

### Descripción
Los proveedores son las empresas o personas que suministran los productos a la farmacia.

### Acceso
- **Ruta:** `/provider/index`
- **Controlador:** `ProviderController@index`
- **Vista:** `resources/views/providers/index.blade.php`

### Campos Requeridos

| Campo | Tipo | Descripción | Requerido |
|-------|------|-------------|-----------|
| `razonsocial` | string | Razón social del proveedor | ✅ Sí |
| `nit` | string | NIT/DUI del proveedor | ❌ No (pero debe ser único) |
| `ncr` | string | Número de Registro de Contribuyente | ❌ No |
| `email` | string | Correo electrónico | ❌ No |
| `company` | integer | ID de la empresa | ✅ Sí |
| `country` | integer | ID del país | ✅ Sí |
| `departament` | integer | ID del departamento | ✅ Sí |
| `municipio` | integer | ID del municipio | ✅ Sí |
| `address` | string | Dirección de referencia | ✅ Sí |
| `tel1` | string | Teléfono principal | ❌ No |
| `tel2` | string | Teléfono secundario | ❌ No |

### Proceso de Creación

1. Acceder a la página de proveedores: `/provider/index`
2. Hacer clic en el botón "Agregar Proveedor"
3. Completar el formulario:
   - **Razón Social:** Nombre legal del proveedor
   - **NCR:** Número de Registro de Contribuyente (opcional)
   - **DUI/NIT:** Documento de identificación (debe ser único)
   - **Correo:** Email de contacto
   - **Empresa:** Seleccionar la empresa asociada
   - **País, Departamento, Municipio:** Ubicación
   - **Dirección:** Dirección completa
   - **Teléfonos:** Teléfono principal y secundario
4. Guardar el formulario

### Validaciones

- El NIT debe ser único en la base de datos
- El NCR debe ser único en la base de datos
- Se crean automáticamente registros en las tablas `phones` y `addresses`

### Ejemplo de Datos

```php
// Se crean automáticamente:
// 1. Phone
$phone = Phone::create([
    'phone' => '2222-3333',
    'phone_fijo' => '2222-4444'
]);

// 2. Address
$address = Address::create([
    'country_id' => 1,
    'department_id' => 1,
    'municipality_id' => 1,
    'reference' => 'Colonia Centro, San Salvador'
]);

// 3. Provider
$provider = Provider::create([
    'razonsocial' => 'Distribuidora Farmacéutica S.A.',
    'nit' => '0614-123456-001-2',
    'ncr' => '12345678',
    'email' => 'contacto@distribuidora.com',
    'company_id' => 1,
    'address_id' => $address->id,
    'phone_id' => $phone->id,
    'user_id' => auth()->id()
]);
```

### Estructura de la Base de Datos

```sql
CREATE TABLE providers (
    id BIGINT PRIMARY KEY,
    razonsocial VARCHAR(255) NOT NULL,
    nit VARCHAR(255) NULL UNIQUE,
    ncr VARCHAR(255) NULL UNIQUE,
    email VARCHAR(255) NULL,
    company_id BIGINT NOT NULL,
    address_id BIGINT NOT NULL,
    phone_id BIGINT NOT NULL,
    user_id BIGINT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## 3. Crear Laboratorios Farmacéuticos

### Descripción
Los laboratorios farmacéuticos son las empresas que fabrican los medicamentos. Este campo es **opcional** para productos.

### Acceso
- **Ruta:** `/pharmaceutical-laboratories`
- **Controlador:** `PharmaceuticalLaboratoryController@index`
- **Vista:** `resources/views/pharmaceutical-laboratories/index.blade.php`

### Campos Requeridos

| Campo | Tipo | Descripción | Requerido |
|-------|------|-------------|-----------|
| `name` | string | Nombre del laboratorio | ✅ Sí (debe ser único) |
| `code` | string | Código del laboratorio | ❌ No (debe ser único si se proporciona) |
| `country` | string | País del laboratorio | ❌ No |
| `phone` | string | Teléfono de contacto | ❌ No |
| `email` | string | Correo electrónico | ❌ No |
| `website` | string | Sitio web | ❌ No |
| `description` | text | Descripción adicional | ❌ No |
| `address` | text | Dirección | ❌ No |

### Proceso de Creación

1. Acceder a la página de laboratorios: `/pharmaceutical-laboratories`
2. Hacer clic en el botón "Crear Nuevo Laboratorio"
3. Completar el formulario:
   - **Nombre:** Ejemplo: "BAYER", "PFIZER", "ROCHE"
   - **Código:** Código interno (opcional)
   - **País:** País de origen
   - **Teléfono, Email, Website:** Información de contacto
   - **Descripción y Dirección:** Información adicional
4. Guardar el formulario

### Ejemplo de Datos

```php
PharmaceuticalLaboratory::create([
    'name' => 'BAYER',
    'code' => 'LAB001',
    'country' => 'Alemania',
    'phone' => '+49 214 30-1',
    'email' => 'info@bayer.com',
    'website' => 'https://www.bayer.com',
    'description' => 'Laboratorio farmacéutico alemán',
    'address' => 'Bayer AG, Leverkusen, Alemania',
    'active' => true
]);
```

### Estructura de la Base de Datos

```sql
CREATE TABLE pharmaceutical_laboratories (
    id BIGINT PRIMARY KEY,
    name VARCHAR(200) NOT NULL UNIQUE,
    code VARCHAR(50) NULL UNIQUE,
    country VARCHAR(100) NULL,
    description TEXT NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(100) NULL,
    address TEXT NULL,
    website VARCHAR(255) NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL
);
```

---

## 4. Registrar Productos

### Descripción
Los productos son los artículos que se venden en la farmacia. Requieren al menos una marca y un proveedor.

### Acceso
- **Ruta:** `/product/index`
- **Controlador:** `ProductController@index`
- **Vista:** `resources/views/products/index.blade.php`

### Dependencias

Antes de crear un producto, asegúrate de tener:
- ✅ Al menos una **Marca** creada (o se usará ID = 1 por defecto)
- ✅ Al menos un **Proveedor** creado (o se usará ID = 1 por defecto)
- ⚪ **Laboratorio Farmacéutico** (opcional)

---

## Campos del Producto

### Sección 1: Información Básica

| Campo | Tipo | Descripción | Requerido |
|-------|------|-------------|-----------|
| `code` | string | Código único del producto | ✅ Sí (único) |
| `name` | string | Nombre del producto | ✅ Sí |
| `description` | text | Descripción detallada | ✅ Sí |
| `image` | file | Imagen del producto | ❌ No |

### Sección 2: Clasificación

| Campo | Tipo | Descripción | Requerido |
|-------|------|-------------|-----------|
| `marca` | integer | ID de la marca | ❌ No (usa ID=1 por defecto) |
| `provider` | integer | ID del proveedor | ❌ No (usa ID=1 por defecto) |
| `category` | string | Categoría del producto | ❌ No |
| `pharmaceutical_laboratory` | integer | ID del laboratorio | ❌ No |

**Categorías disponibles:**
- Analgésicos y Antiinflamatorios
- Antibióticos
- Antivirales
- Antimicóticos
- Antiparasitarios
- Antihistamínicos y Antialérgicos
- Antigripales
- Antitusivos y Expectorantes
- Antihipertensivos
- Y muchas más... (ver lista completa en el formulario)

### Sección 3: Información Farmacéutica

| Campo | Tipo | Descripción | Requerido |
|-------|------|-------------|-----------|
| `presentation_type` | string | Tipo de presentación | ❌ No |
| `specialty` | string | Especialidad médica | ❌ No |
| `unit_measure` | string | Unidad de medida | ❌ No |
| `registration_number` | string | Número de registro sanitario | ❌ No |
| `sale_form` | string | Forma de venta | ❌ No |
| `product_type` | string | Tipo de producto | ❌ No |
| `formula` | text | Fórmula/Ingredientes activos | ❌ No |

**Opciones de Presentación:**
- caja
- blister
- pastilla
- ampolla
- frasco
- tubo
- sobre
- otro

**Opciones de Forma de Venta:**
- Venta libre
- Con receta
- Controlado
- Sustancia controlada

**Opciones de Tipo de Producto:**
- Monofármico
- Polifármico
- Genérico
- Similar
- Biótico

### Sección 4: Configuración de Presentaciones para Inventario

| Campo | Tipo | Descripción | Requerido |
|-------|------|-------------|-----------|
| `pastillas_per_blister` | integer | Pastillas por blister | ❌ No |
| `blisters_per_caja` | integer | Blisters por caja | ❌ No |
| `sale_type` | string | Tipo de venta (weight/volume/unit) | ❌ No |
| `weight_per_unit` | decimal | Peso por unidad (si sale_type = weight) | ❌ No |
| `volume_per_unit` | decimal | Volumen por unidad (si sale_type = volume) | ❌ No |
| `content_per_unit` | decimal | Contenido por unidad | ❌ No |

> **Nota:** El inventario se guarda en **pastillas** (unidad base). El sistema calcula automáticamente las conversiones.

### Sección 5: Información Fiscal y Comercial

| Campo | Tipo | Descripción | Requerido |
|-------|------|-------------|-----------|
| `cfiscal` | string | Condición fiscal | ✅ Sí |
| `type` | string | Tipo de venta | ✅ Sí |
| `price` | decimal | Precio del producto | ✅ Sí |

**Opciones de Condición Fiscal:**
- `gravado` - Producto gravado con IVA
- `exento` - Producto exento de IVA

**Opciones de Tipo:**
- `directo` - Venta directa
- `tercero` - Venta a terceros

### Proceso de Creación de Producto

1. **Acceder a la página de productos:** `/product/index`

2. **Hacer clic en "Crear nuevo producto"** o botón similar

3. **Completar las secciones del formulario:**

   **a) Información Básica:**
   - Código del producto (único, obligatorio)
   - Nombre del producto (obligatorio)
   - Descripción (obligatorio)
   - Imagen (opcional)

   **b) Clasificación:**
   - Seleccionar Marca (desde dropdown)
   - Seleccionar Proveedor (desde dropdown)
   - Seleccionar Categoría (opcional)
   - Seleccionar Laboratorio Farmacéutico (opcional)

   **c) Información Farmacéutica:**
   - Tipo de presentación
   - Especialidad
   - Unidad de medida
   - Número de registro sanitario
   - Forma de venta
   - Tipo de producto
   - Fórmula/Ingredientes activos

   **d) Configuración de Presentaciones:**
   - Pastillas por blister
   - Blisters por caja
   - Tipo de venta (si aplica)
   - Peso/Volumen por unidad (si aplica)

   **e) Información Fiscal y Comercial:**
   - Condición fiscal (gravado/exento) - **OBLIGATORIO**
   - Tipo (directo/tercero) - **OBLIGATORIO**
   - Precio - **OBLIGATORIO**

4. **Guardar el producto**

5. **El sistema automáticamente:**
   - Crea conversiones de unidades por defecto
   - Asigna el usuario actual como creador
   - Establece el estado como activo (state = 1)

### Ejemplo de Datos

```php
Product::create([
    'code' => 'PROD001',
    'name' => 'Aspirina 500mg',
    'description' => 'Tabletas de ácido acetilsalicílico 500mg',
    'state' => 1,
    'cfiscal' => 'gravado',
    'type' => 'directo',
    'price' => 2.50,
    'marca_id' => 1,
    'provider_id' => 1,
    'pharmaceutical_laboratory_id' => 1,
    'category' => 'Analgésicos y Antiinflamatorios',
    'presentation_type' => 'caja',
    'specialty' => 'Medicina General',
    'registration_number' => 'REG-12345',
    'formula' => 'Ácido acetilsalicílico 500mg',
    'unit_measure' => 'mg',
    'sale_form' => 'Venta libre',
    'product_type' => 'Monofármico',
    'pastillas_per_blister' => 10,
    'blisters_per_caja' => 2,
    'user_id' => auth()->id()
]);
```

### Validaciones

El sistema valida:
- ✅ Código único (no puede repetirse)
- ✅ Nombre requerido
- ✅ Descripción requerida
- ✅ Condición fiscal válida (gravado/exento)
- ✅ Tipo válido (directo/tercero)
- ✅ Precio numérico y mayor o igual a 0
- ✅ Imagen válida (si se proporciona): jpeg, png, jpg, webp, gif, máximo 5MB
- ✅ Marca existe (si se proporciona)
- ✅ Proveedor existe (si se proporciona)
- ✅ Laboratorio existe (si se proporciona)

### Estructura de la Base de Datos

```sql
CREATE TABLE products (
    id BIGINT PRIMARY KEY,
    code VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    state TINYINT DEFAULT 1,
    cfiscal ENUM('gravado', 'exento') NOT NULL,
    type ENUM('directo', 'tercero') NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    marca_id BIGINT NOT NULL,
    provider_id BIGINT NOT NULL,
    pharmaceutical_laboratory_id BIGINT NULL,
    category VARCHAR(255) NULL,
    presentation_type VARCHAR(50) NULL,
    specialty VARCHAR(255) NULL,
    registration_number VARCHAR(100) NULL,
    formula TEXT NULL,
    unit_measure VARCHAR(50) NULL,
    sale_form VARCHAR(50) NULL,
    product_type VARCHAR(50) NULL,
    pastillas_per_blister INT NULL,
    blisters_per_caja INT NULL,
    sale_type VARCHAR(50) NULL,
    weight_per_unit DECIMAL(10,4) NULL,
    volume_per_unit DECIMAL(10,4) NULL,
    content_per_unit DECIMAL(10,4) NULL,
    image VARCHAR(255) NULL,
    user_id BIGINT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (marca_id) REFERENCES marcas(id),
    FOREIGN KEY (provider_id) REFERENCES providers(id),
    FOREIGN KEY (pharmaceutical_laboratory_id) REFERENCES pharmaceutical_laboratories(id)
);
```

---

## Rutas y Accesos

### Rutas de Marcas

```
GET  /marcas/index              - Listar marcas
GET  /marcas/getmarcas          - Obtener todas las marcas (JSON)
GET  /marcas/getmarcaid/{id}    - Obtener marca por ID (JSON)
POST /marcas/store              - Crear nueva marca
PATCH /marcas/update            - Actualizar marca
GET  /marcas/destroy/{id}       - Eliminar marca
```

### Rutas de Proveedores

```
GET  /provider/index            - Listar proveedores
GET  /provider/getproviders     - Obtener todos los proveedores (JSON)
GET  /provider/getproviderid/{id} - Obtener proveedor por ID (JSON)
POST /provider/store            - Crear nuevo proveedor
PATCH /provider/update          - Actualizar proveedor
GET  /provider/destroy/{id}    - Eliminar proveedor
POST /provider/validate-ncr     - Validar NCR único
POST /provider/validate-nit     - Validar NIT único
```

### Rutas de Laboratorios Farmacéuticos

```
GET    /pharmaceutical-laboratories              - Listar laboratorios
GET    /pharmaceutical-laboratories/create       - Formulario de creación
POST   /pharmaceutical-laboratories             - Crear laboratorio
GET    /pharmaceutical-laboratories/{id}        - Ver laboratorio
GET    /pharmaceutical-laboratories/{id}/edit   - Editar laboratorio
PATCH  /pharmaceutical-laboratories/{id}        - Actualizar laboratorio
DELETE /pharmaceutical-laboratories/{id}        - Eliminar laboratorio
GET    /pharmaceutical-laboratories/get/laboratories - Obtener laboratorios (JSON)
```

### Rutas de Productos

```
GET  /product/index                    - Listar productos
GET  /product/create                   - Formulario de creación
POST /product/store                    - Crear producto
GET  /product/getproductid/{id}        - Obtener producto por ID (JSON)
GET  /product/getproductcode/{code}   - Obtener producto por código (JSON)
GET  /product/getproductall            - Obtener todos los productos (JSON)
PATCH /product/update                  - Actualizar producto
GET  /product/destroy/{id}            - Eliminar producto
```

---

## Flujo Completo de Ejemplo

### Paso 1: Crear Marca
```
1. Ir a /marcas/index
2. Clic en "Agregar Marca"
3. Nombre: "Bayer"
4. Descripción: "Laboratorio alemán"
5. Guardar
```

### Paso 2: Crear Proveedor
```
1. Ir a /provider/index
2. Clic en "Agregar Proveedor"
3. Razón Social: "Distribuidora Farmacéutica S.A."
4. NIT: "0614-123456-001-2"
5. Seleccionar empresa, país, departamento, municipio
6. Dirección: "Colonia Centro, San Salvador"
7. Teléfonos: "2222-3333", "2222-4444"
8. Guardar
```

### Paso 3: Crear Laboratorio (Opcional)
```
1. Ir a /pharmaceutical-laboratories
2. Clic en "Crear Nuevo Laboratorio"
3. Nombre: "BAYER"
4. País: "Alemania"
5. Guardar
```

### Paso 4: Crear Producto
```
1. Ir a /product/index
2. Clic en "Crear nuevo producto"
3. Información Básica:
   - Código: "ASP500"
   - Nombre: "Aspirina 500mg"
   - Descripción: "Tabletas de ácido acetilsalicílico"
4. Clasificación:
   - Marca: Seleccionar "Bayer"
   - Proveedor: Seleccionar "Distribuidora Farmacéutica S.A."
   - Categoría: "Analgésicos y Antiinflamatorios"
   - Laboratorio: Seleccionar "BAYER"
5. Información Farmacéutica:
   - Presentación: "caja"
   - Forma de venta: "Venta libre"
   - Tipo: "Monofármico"
6. Configuración:
   - Pastillas por blister: 10
   - Blisters por caja: 2
7. Información Fiscal:
   - Condición fiscal: "gravado"
   - Tipo: "directo"
   - Precio: 2.50
8. Guardar
```

---

## Notas Importantes

1. **Valores por Defecto:** Si no se selecciona una marca o proveedor, el sistema usa ID = 1. Asegúrate de que existan registros con estos IDs o crea los registros necesarios.

2. **Código Único:** El código del producto debe ser único en toda la base de datos. No se pueden duplicar códigos.

3. **Conversiones Automáticas:** Al crear un producto, el sistema crea automáticamente conversiones de unidades por defecto para el manejo del inventario.

4. **Imágenes:** Las imágenes deben ser archivos válidos (jpeg, png, jpg, webp, gif) con un tamaño máximo de 5MB.

5. **Soft Deletes:** Los laboratorios farmacéuticos usan soft deletes, por lo que al eliminarlos no se borran físicamente de la base de datos.

6. **Relaciones:** 
   - Un producto pertenece a una marca
   - Un producto pertenece a un proveedor
   - Un producto puede pertenecer a un laboratorio farmacéutico (opcional)
   - Una marca puede tener muchos productos
   - Un proveedor puede tener muchos productos
   - Un laboratorio puede tener muchos productos

---

## Solución de Problemas

### Error: "El código del producto ya existe"
- **Solución:** Usa un código diferente. El código debe ser único.

### Error: "La marca no existe"
- **Solución:** Crea la marca primero en `/marcas/index` o verifica que el ID de marca sea válido.

### Error: "El proveedor no existe"
- **Solución:** Crea el proveedor primero en `/provider/index` o verifica que el ID de proveedor sea válido.

### Error: "El laboratorio no existe"
- **Solución:** Crea el laboratorio primero en `/pharmaceutical-laboratories` o deja el campo vacío si no es necesario.

### Error al subir imagen
- **Solución:** Verifica que el archivo sea una imagen válida (jpeg, png, jpg, webp, gif) y que no exceda 5MB.

---

## Contacto y Soporte

Para más información o ayuda, consulta:
- Controladores: `app/Http/Controllers/`
- Modelos: `app/Models/`
- Vistas: `resources/views/`
- Migraciones: `database/migrations/`

---

**Última actualización:** Enero 2025
**Versión del sistema:** 1.0
