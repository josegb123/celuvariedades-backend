# Documentación de la API de CeluVariedades

Este documento proporciona una guía completa para la instalación, uso y comprensión de la API de CeluVariedades.

## Tabla de Contenidos

1.  [Instalación](#instalación)
    *   [Requisitos Previos](#requisitos-previos)
    *   [Clonar el Repositorio (para no expertos en Git)](#clonar-el-repositorio-para-no-expertos-en-git)
    *   [Configuración del Entorno](#configuración-del-entorno)
    *   [Instalación de Dependencias](#instalación-de-dependencias)
    *   [Generación de Claves](#generación-de-claves)
    *   [Configuración de la Base de Datos](#configuración-de-la-base-de-datos)
    *   [Migraciones y Seeds](#migraciones-y-seeds)
    *   [Iniciar el Servidor](#iniciar-el-servidor)
2.  [Uso de la API](#uso-de-la-api)
    *   [Autenticación](#autenticación)
    *   [Formato de Peticiones y Respuestas](#formato-de-peticiones-y-respuestas)
3.  [Endpoints](#endpoints)
    *   [Autenticación](#autenticación-1)
    *   [Clientes](#clientes)
    *   [Ventas](#ventas)
    *   [Carteras](#carteras)
    *   [Movimientos Financieros](#movimientos-financieros)
    *   [Tipos de Movimientos Financieros](#tipos-de-movimientos-financieros)
    *   [Categorías](#categorías)
    *   [Facturas](#facturas)
    *   [Productos](#productos)
    *   [Usuarios](#usuarios)
    *   [Proveedores](#proveedores)
    *   [Cuentas por Cobrar](#cuentas-por-cobrar)
    *   [Abonos](#abonos)
    *   [Caja Diaria](#caja-diaria)
    *   [Estadísticas](#estadísticas)
4.  [Estructura y Arquitectura](#estructura-y-arquitectura)
    *   [Patrón MVC](#patrón-mvc)
    *   [Estructura de Directorios Clave](#estructura-de-directorios-clave)
    *   [Capa de Servicios](#capa-de-servicios)

---

## 1. Instalación

Para poner en marcha el proyecto, sigue los siguientes pasos.

### Requisitos Previos

Asegúrate de tener instalado lo siguiente en tu sistema:

*   **PHP** (versión 8.1 o superior): [Descargar PHP](https://www.php.net/downloads.php)
*   **Composer**: Gestor de dependencias de PHP. [Instalar Composer](https://getcomposer.org/download/)
*   **Node.js** (versión 16 o superior) y **pnpm**: Para las dependencias de frontend (aunque esta API es backend, puede haber scripts de desarrollo).
    *   [Descargar Node.js](https://nodejs.org/es/download/)
    *   Instalar pnpm: `npm install -g pnpm`
*   **Servidor de Base de Datos**: MySQL o PostgreSQL (u otro compatible con Laravel).
*   **Git**: Sistema de control de versiones. [Descargar Git](https://git-scm.com/downloads)

### Clonar el Repositorio (para no expertos en Git)

Si no estás familiarizado con Git, sigue estos pasos:

1.  **Instalar Git**: Descarga e instala Git desde el enlace provisto en los requisitos previos. Durante la instalación, puedes aceptar las opciones por defecto si no estás seguro.
2.  **Abrir la Terminal/Línea de Comandos**:
    *   En Windows: Busca "cmd" o "PowerShell" en el menú de inicio.
    *   En macOS/Linux: Abre la aplicación "Terminal".
3.  **Navegar a la Carpeta Deseada**: Usa el comando `cd` (change directory) para ir a la carpeta donde quieres guardar el proyecto. Por ejemplo:
    ```bash
    cd C:\Users\TuUsuario\Documentos\Proyectos
    # O en Linux/macOS
    cd ~/Documentos/Proyectos
    ```
4.  **Clonar el Repositorio**: Ejecuta el siguiente comando. Si no tienes la URL del repositorio, solicítala a quien te proporcionó el proyecto.
    ```bash
    git clone <URL_DEL_REPOSITORIO>
    ```
    Esto creará una nueva carpeta con el nombre del proyecto y descargará todos los archivos dentro.
5.  **Entrar a la Carpeta del Proyecto**:
    ```bash
    cd celuvariedades-backend
    ```

### Configuración del Entorno

1.  **Duplicar el archivo .env**: Copia el archivo `.env.example` y renómbralo a `.env` en la raíz del proyecto.
    ```bash
    cp .env.example .env
    ```
2.  **Editar el archivo .env**: Abre el archivo `.env` con un editor de texto y configura las credenciales de tu base de datos y otras variables de entorno necesarias.

    ```dotenv
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=celuvariedades
    DB_USERNAME=root
    DB_PASSWORD=
    ```

### Instalación de Dependencias

Ejecuta los siguientes comandos para instalar las dependencias de PHP y JavaScript:

```bash
composer install
pnpm install
```

### Generación de Claves

Genera la clave de aplicación de Laravel:

```bash
php artisan key:generate
```

### Configuración de la Base de Datos

Asegúrate de que tu servidor de base de datos esté corriendo y que hayas creado una base de datos con el nombre especificado en tu archivo `.env` (ej. `celuvariedades`).

### Migraciones y Seeds

Ejecuta las migraciones de la base de datos para crear las tablas y, opcionalmente, los seeds para poblar la base de datos con datos de prueba:

```bash
php artisan migrate
php artisan db:seed # Opcional: para datos de prueba
```

### Iniciar el Servidor

Puedes iniciar el servidor de desarrollo de Laravel con:

```bash
php artisan serve
```

La API estará disponible en `http://127.0.0.1:8000` (o el puerto que se indique).

## 2. Uso de la API

### Autenticación

La API utiliza autenticación basada en tokens (Laravel Sanctum). Para acceder a las rutas protegidas, primero debes registrarte (`/register`) o iniciar sesión (`/login`) para obtener un token de acceso. Este token debe enviarse en todas las peticiones a rutas protegidas en el encabezado `Authorization` como un `Bearer Token`.

**Ejemplo de Encabezado:**
`Authorization: Bearer {your_token_here}`

### Formato de Peticiones y Respuestas

*   Todas las peticiones deben enviar el encabezado `Accept: application/json`.
*   Las peticiones `POST` y `PUT` que envíen datos en el cuerpo deben usar el encabezado `Content-Type: application/json`.
*   Las respuestas de la API serán siempre en formato JSON.

## 3. Endpoints

A continuación, se listan los principales endpoints de la API, sus métodos HTTP, y una breve descripción de lo que se espera enviar/recibir.

**Base URL**: `http://127.00.1:8000/api` (o la URL de tu entorno)

### Autenticación

*   **`POST /register`**
    *   Descripción: Registra un nuevo usuario.
    *   Request Body (JSON): `{ "name": "...", "email": "...", "password": "...", "password_confirmation": "..." }`
    *   Response (JSON): `{ "user": {...}, "token": "..." }`
*   **`POST /login`**
    *   Descripción: Inicia sesión y obtiene un token de acceso.
    *   Request Body (JSON): `{ "email": "...", "password": "..." }`
    *   Response (JSON): `{ "user": {...}, "token": "..." }`
*   **`POST /logout`** (Protegido)
    *   Descripción: Cierra la sesión del usuario actual revocando el token.
    *   Response (JSON): `{ "message": "Logged out" }`
*   **`GET /user`** (Protegido)
    *   Descripción: Obtiene los datos del usuario autenticado.
    *   Response (JSON): `{ "id": ..., "name": "...", "email": "..." }`

### Clientes

*   **`GET /clientes`** (Protegido)
    *   Descripción: Obtiene una lista de todos los clientes.
*   **`GET /clientes/{id}`** (Protegido)
    *   Descripción: Obtiene los detalles de un cliente específico.
*   **`POST /clientes`** (Protegido)
    *   Descripción: Crea un nuevo cliente.
    *   Request Body (JSON): `{ "nombre": "...", "apellido": "...", "telefono": "..." }`
*   **`PUT /clientes/{id}`** (Protegido)
    *   Descripción: Actualiza un cliente existente.
    *   Request Body (JSON): `{ "nombre": "...", "apellido": "...", "telefono": "..." }`
*   **`DELETE /clientes/{id}`** (Protegido)
    *   Descripción: Elimina un cliente.

### Ventas

*   **`GET /ventas`** (Protegido)
    *   Descripción: Obtiene una lista de todas las ventas.
*   **`GET /ventas/{id}`** (Protegido)
    *   Descripción: Obtiene los detalles de una venta específica.
*   **`POST /ventas`** (Protegido)
    *   Descripción: Crea una nueva venta.
    *   Request Body (JSON): `{ "cliente_id": ..., "productos": [{ "producto_id": ..., "cantidad": ..., "precio_unitario": ... }], "metodo_pago": "..." }`
*   **`PUT /ventas/{id}`** (Protegido)
    *   Descripción: Actualiza una venta existente.
*   **`DELETE /ventas/{id}`** (Protegido)
    *   Descripción: Elimina una venta.

### Carteras

*   **`POST /carteras`** (Protegido)
    *   Descripción: Crea una nueva cartera. (Probablemente asociada a un cliente o venta)
*   **`GET /carteras/{id}`** (Protegido)
    *   Descripción: Obtiene los detalles de una cartera específica.
*   **`PUT /carteras/{id}`** (Protegido)
    *   Descripción: Actualiza una cartera existente.

### Movimientos Financieros

*   **`GET /movimientos-financieros`** (Protegido)
    *   Descripción: Obtiene una lista de todos los movimientos financieros.
*   **`GET /movimientos-financieros/{id}`** (Protegido)
    *   Descripción: Obtiene los detalles de un movimiento financiero específico.
*   **`POST /movimientos-financieros`** (Protegido)
    *   Descripción: Crea un nuevo movimiento financiero.
*   **`PUT /movimientos-financieros/{id}`** (Protegido)
    *   Descripción: Actualiza un movimiento financiero existente.
*   **`DELETE /movimientos-financieros/{id}`** (Protegido)
    *   Descripción: Elimina un movimiento financiero.

### Tipos de Movimientos Financieros

*   **`GET /tipo-movimientos-financieros`** (Protegido)
    *   Descripción: Obtiene una lista de todos los tipos de movimientos financieros.
*   **`GET /tipo-movimientos-financieros/{id}`** (Protegido)
    *   Descripción: Obtiene los detalles de un tipo de movimiento financiero específico.
*   **`POST /tipo-movimientos-financieros`** (Protegido)
    *   Descripción: Crea un nuevo tipo de movimiento financiero.
*   **`PUT /tipo-movimientos-financieros/{id}`** (Protegido)
    *   Descripción: Actualiza un tipo de movimiento financiero existente.
*   **`DELETE /tipo-movimientos-financieros/{id}`** (Protegido)
    *   Descripción: Elimina un tipo de movimiento financiero.

### Categorías

*   **`GET /categorias`** (Protegido)
    *   Descripción: Obtiene una lista de todas las categorías.
*   **`GET /categorias/{id}`** (Protegido)
    *   Descripción: Obtiene los detalles de una categoría específica.
*   **`POST /categorias`** (Protegido)
    *   Descripción: Crea una nueva categoría.
*   **`PUT /categorias/{id}`** (Protegido)
    *   Descripción: Actualiza una categoría existente.
*   **`DELETE /categorias/{id}`** (Protegido)
    *   Descripción: Elimina una categoría.

### Facturas

*   **`GET /facturas`** (Protegido)
    *   Descripción: Obtiene una lista de todas las facturas.
*   **`GET /facturas/{id}`** (Protegido)
    *   Descripción: Obtiene los detalles de una factura específica.
*   **`POST /facturas`** (Protegido)
    *   Descripción: Crea una nueva factura.
*   **`PUT /facturas/{id}`** (Protegido)
    *   Descripción: Actualiza una factura existente.
*   **`DELETE /facturas/{id}`** (Protegido)
    *   Descripción: Elimina una factura.

### Productos

*   **`GET /productos`** (Protegido)
    *   Descripción: Obtiene una lista de todos los productos.
*   **`GET /productos/{id}`** (Protegido)
    *   Descripción: Obtiene los detalles de un producto específico.
*   **`POST /productos`** (Protegido)
    *   Descripción: Crea un nuevo producto. (Soporta `multipart/form-data` si hay carga de imágenes).
    *   Request Body (Form Data/JSON): `{ "nombre": "...", "descripcion": "...", "precio": ..., "stock": ..., "categoria_id": ..., "imagen": (File) }`
*   **`POST /productos/{producto}`** (Protegido)
    *   Descripción: Actualiza un producto existente (usa POST para `multipart/form-data` con `_method=PUT`).
*   **`DELETE /productos/{id}`** (Protegido)
    *   Descripción: Elimina un producto.

### Usuarios

*   **`GET /usuarios`** (Protegido)
    *   Descripción: Obtiene una lista de todos los usuarios.
*   **`GET /usuarios/{id}`** (Protegido)
    *   Descripción: Obtiene los detalles de un usuario específico.
*   **`POST /usuarios`** (Protegido)
    *   Descripción: Crea un nuevo usuario.
*   **`PUT /usuarios/{id}`** (Protegido)
    *   Descripción: Actualiza un usuario existente.
*   **`DELETE /usuarios/{id}`** (Protegido)
    *   Descripción: Elimina un usuario (soft delete).
*   **`PUT /usuarios/{id}/restore`** (Protegido)
    *   Descripción: Restaura un usuario eliminado previamente.

### Proveedores

*   **`GET /proveedor`** (Protegido)
    *   Descripción: Obtiene una lista de todos los proveedores.
*   **`GET /proveedor/{id}`** (Protegido)
    *   Descripción: Obtiene los detalles de un proveedor específico.
*   **`POST /proveedor`** (Protegido)
    *   Descripción: Crea un nuevo proveedor.
*   **`PUT /proveedor/{id}`** (Protegido)
    *   Descripción: Actualiza un proveedor existente.
*   **`DELETE /proveedor/{id}`** (Protegido)
    *   Descripción: Elimina un proveedor.

### Cuentas por Cobrar

*   **`GET /cuentas-por-cobrar`** (Protegido)
    *   Descripción: Obtiene una lista de todas las cuentas por cobrar.
*   **`GET /cuentas-por-cobrar/{id}`** (Protegido)
    *   Descripción: Obtiene los detalles de una cuenta por cobrar específica.

### Abonos

*   **`POST /abonos`** (Protegido)
    *   Descripción: Registra un nuevo abono a una cartera.
    *   Request Body (JSON): `{ "cartera_id": ..., "monto": ..., "fecha_abono": "YYYY-MM-DD" }`

### Caja Diaria

*   **`GET /cajas/activa`** (Protegido)
    *   Descripción: Obtiene la información de la caja diaria activa del usuario.
*   **`POST /cajas/apertura`** (Protegido)
    *   Descripción: Abre una nueva sesión de caja diaria.
    *   Request Body (JSON): `{ "monto_inicial": ... }`
*   **`POST /cajas/{cajaDiaria}/cierre`** (Protegido)
    *   Descripción: Cierra una sesión específica de caja diaria.

### Estadísticas

*   **`GET /estadisticas/ticket-promedio`** (Protegido)
    *   Descripción: Obtiene el valor del ticket promedio de ventas.
*   **`GET /estadisticas/historial-ganancias`** (Protegido)
    *   Descripción: Obtiene el historial de ganancias a lo largo del tiempo.
*   **`GET /estadisticas/productos-bajo-stock`** (Protegido)
    *   Descripción: Obtiene una lista de productos con stock bajo.
*   **`GET /estadisticas/top-clientes`** (Protegido)
    *   Descripción: Obtiene un ranking de los clientes con más compras.
*   **`GET /estadisticas/top-productos`** (Protegido)
    *   Descripción: Obtiene un ranking de los productos más vendidos.
*   **`GET /estadisticas/ventas-por-periodo`** (Protegido)
    *   Descripción: Obtiene el volumen de ventas por un periodo determinado.
*   **`GET /estadisticas/historial-ventas`** (Protegido)
    *   Descripción: Obtiene el historial de ventas a lo largo del tiempo (parecido a historial-ganancias).

## 4. Estructura y Arquitectura

Este proyecto sigue la arquitectura típica de una aplicación Laravel, que se basa principalmente en el patrón **Modelo-Vista-Controlador (MVC)**, aunque en una API REST las "Vistas" son reemplazadas por las respuestas JSON que consumen los clientes.

### Patrón MVC

*   **Modelos (`app/Models`)**: Representan la estructura de las tablas de la base de datos y contienen la lógica de negocio relacionada con los datos (ej. `Cliente.php`, `Producto.php`).
*   **Controladores (`app/Http/Controllers`)**: Manejan las peticiones HTTP entrantes, interactúan con los modelos (y posiblemente servicios) y devuelven las respuestas apropiadas (ej. `ClienteController.php`, `VentaController.php`).
*   **Rutas (`routes/api.php`)**: Definen los endpoints de la API y mapean las URLs a los métodos de los controladores.

### Estructura de Directorios Clave

*   `app/Http/Controllers`: Contiene todos los controladores de la aplicación que manejan la lógica de negocio de las rutas.
*   `app/Models`: Aloja las clases de los modelos Eloquent que interactúan con la base de datos.
*   `app/Http/Requests`: Define las reglas de validación y autorización para las peticiones HTTP.
*   `app/Http/Resources`: Transforma los modelos Eloquent en arrays/JSON de una manera limpia y estandarizada para las respuestas de la API.
*   `app/Services`: Contiene la lógica de negocio compleja y reutilizable, desacoplando los controladores.
*   `database/migrations`: Archivos que definen la estructura de la base de datos (tablas, columnas, relaciones).
*   `database/seeders`: Clases para poblar la base de datos con datos de prueba o iniciales.
*   `routes/api.php`: Definición de todas las rutas de la API.
*   `config`: Archivos de configuración de la aplicación (base de datos, servicios, etc.).
*   `public`: El directorio raíz web, contiene el `index.php` que arranca la aplicación.

### Capa de Servicios

Se observa la implementación de una capa de servicios (`app/Services`), lo que indica una buena práctica de separación de responsabilidades. Los controladores delegan la lógica de negocio más compleja a estas clases de servicio, manteniéndolos delgados y enfocados en la manipulación de la petición y la respuesta.

**Ejemplo:** `app/Services/VentaService.php` manejaría la lógica de creación, actualización y eliminación de ventas, incluyendo la interacción con el inventario, movimientos financieros, etc.