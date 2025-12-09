# Documento Final de Proyecto: CELUVARIEDADES M y K - Sistema de Gestión Integrado para Comercio y Almacén

**Autor(es):** [Nombre del Autor/Equipo]
**Fecha:** Diciembre 2025

---

## Tabla de Contenidos

1.  [Introducción](#1-introducción)
2.  [Planteamiento del Problema](#2-planteamiento-del-problema)
3.  [Marco Teórico](#3-marco-teórico)
    *   [Sistemas ERP/Mini-ERP](#31-sistemas-erpmini-erp)
    *   [Tecnologías de Desarrollo](#32-tecnologías-de-desarrollo)
    *   [Metodologías de Desarrollo](#32-metodologías-de-desarrollo)
4.  [Marco Metodológico](#4-marco-metodológico)
5.  [Análisis y Diseño del Sistema (Resultados)](#5-análisis-y-diseño-del-sistema-resultados)
    *   [Arquitectura del Sistema](#51-arquitectura-del-sistema)
    *   [Módulos del Sistema](#52-módulos-del-sistema)
    *   [Modelos de Datos Principales](#53-modelos-de-datos-principales)
    *   [Requisitos Funcionales y No Funcionales](#54-requisitos-funcionales-y-no-funcionales)
6.  [Conclusiones y Recomendaciones](#6-conclusiones-y-recomendaciones)
7.  [Referencias APA](#7-referencias-apa)

---

## 1. Introducción

El presente documento final detalla el desarrollo e implementación del sistema de gestión integrado **CELUVARIEDADES M y K**, una solución informática diseñada para optimizar las operaciones de comercio y almacén de una pequeña o mediana empresa (PyME). En un entorno comercial cada vez más competitivo, la eficiencia en la gestión de inventario, ventas, compras y finanzas se vuelve crucial para la sostenibilidad y el crecimiento. Este proyecto aborda la necesidad de sistematizar procesos que tradicionalmente se realizan de forma manual o con herramientas fragmentadas, introduciendo una plataforma robusta y accesible.

El sistema CELUVARIEDADES M y K se concibe como un Mini-ERP, adaptado a las necesidades específicas de negocios dedicados a la venta de productos, permitiendo un control centralizado y una toma de decisiones informada. Desde la propuesta inicial del proyecto, se han incorporado diversas funcionalidades, decisiones técnicas estratégicas y mejoras en la arquitectura para ofrecer una herramienta integral que abarque la gestión de clientes, productos, proveedores, ventas en punto de venta (POS), historial de transacciones, movimientos financieros y un módulo avanzado de reportes y estadísticas.

El objetivo general del sistema es mejorar la productividad, reducir errores operativos y proporcionar visibilidad en tiempo real sobre el desempeño del negocio. Este documento estructura el análisis, diseño y resultados del proyecto, detallando la problemática original, el marco teórico y metodológico, la arquitectura implementada, los módulos desarrollados y las conclusiones obtenidas, sirviendo como un referente técnico-académico del esfuerzo realizado.

## 2. Planteamiento del Problema

CELUVARIEDADES M y K, como muchas PyMEs en el sector de comercio y distribución, enfrenta desafíos significativos derivados de la falta de un sistema de gestión integrado y eficiente. La gestión manual o semi-automatizada de sus operaciones cotidianas conlleva una serie de problemáticas que impactan directamente su rentabilidad y capacidad de crecimiento:

*   **Control de Inventario Deficiente:** Dificultad para conocer el stock real de productos en tiempo real, lo que lleva a quiebres de stock, exceso de inventario de productos de baja rotación, pérdidas por obsolescencia y errores en los pedidos a proveedores. La identificación manual de productos bajo un umbral de stock es lenta y propensa a fallos.
*   **Procesos de Venta Ineficientes:** La ausencia de un sistema de Punto de Venta (POS) ágil ralentiza las transacciones, generando colas y afectando la experiencia del cliente. La gestión de devoluciones y la creación de facturas manuales son procesos tediosos y susceptibles a errores.
*   **Falta de Visibilidad Financiera:** El seguimiento de los movimientos financieros, ingresos, egresos y, especialmente, las cuentas por cobrar y los abonos de clientes, se realiza de manera descoordinada, impidiendo una visión clara de la salud financiera del negocio. La estimación de ganancias y la detección de tendencias es un desafío.
*   **Gestión de Clientes y Proveedores Dispersa:** La información de clientes y proveedores no está centralizada, lo que dificulta la identificación de clientes recurrentes, la gestión de la cartera de cobro (o saldos a favor del cliente) y la optimización de las relaciones con los proveedores.
*   **Escasa Capacidad Analítica:** La ausencia de herramientas para generar reportes y estadísticas significativas impide a la gerencia identificar tendencias de ventas, evaluar el rendimiento de productos, analizar la rentabilidad por cliente o controlar los gastos con proveedores, limitando la toma de decisiones estratégicas.

Estos problemas resultan en una operación ineficiente, insatisfacción del cliente, pérdida de ingresos potenciales y una limitada capacidad para escalar el negocio. Se requiere una solución tecnológica que integre estas operaciones, automatice procesos clave y proporcione las herramientas analíticas necesarias para una gestión proactiva.

## 3. Marco Teórico

### 3.1 Sistemas ERP/Mini-ERP

Un **Enterprise Resource Planning (ERP)** es un sistema de gestión empresarial que integra y automatiza muchas de las prácticas de negocio asociadas con los aspectos operativos o productivos de una empresa, en particular con la producción, distribución, recursos humanos y finanzas. Su objetivo principal es facilitar el flujo de información entre todas las funciones de la organización y gestionar las conexiones con los stakeholders externos.

Para PyMEs, el concepto de **Mini-ERP** es más adecuado. Un Mini-ERP se refiere a una versión simplificada y a menudo más asequible de un sistema ERP, diseñada para satisfacer las necesidades específicas de empresas más pequeñas que no requieren la complejidad ni la inversión de un ERP a gran escala. CELUVARIEDADES M y K se enmarca en esta categoría, buscando un sistema que centralice las operaciones críticas sin la sobrecarga de un ERP completo. Los beneficios clave incluyen:

*   **Centralización de Datos:** Elimina la redundancia y mejora la consistencia de la información.
*   **Automatización de Procesos:** Reduce el trabajo manual y los errores operativos.
*   **Visibilidad en Tiempo Real:** Permite un monitoreo constante del rendimiento del negocio.
*   **Mejora en la Toma de Decisiones:** Proporciona datos fiables y herramientas analíticas para decisiones estratégicas.

### 3.2 Tecnologías de Desarrollo

El desarrollo de CELUVARIEDADES M y K se fundamenta en un stack tecnológico moderno y robusto, ideal para aplicaciones web dinámicas y escalables. La arquitectura cliente-servidor se implementa mediante una API RESTful que comunica un frontend interactivo con un backend sólido.

#### Backend: Laravel (PHP Framework)

**Laravel** es un framework de desarrollo de aplicaciones web de código abierto escrito en PHP. Se caracteriza por su sintaxis elegante y expresiva, que facilita el desarrollo rápido y mantenible. Para CELUVARIEDADES M y K, Laravel se ha elegido por:

*   **Arquitectura MVC (Modelo-Vista-Controlador):** Organiza el código de manera lógica, mejorando la modularidad y la escalabilidad.
*   **Eloquent ORM:** Proporciona una interfaz elegante para interactuar con la base de datos, simplificando las operaciones CRUD (Crear, Leer, Actualizar, Eliminar).
*   **RESTful APIs:** Facilita la construcción de interfaces de programación de aplicaciones robustas y bien definidas para la comunicación con el frontend.
*   **Autenticación y Autorización (Laravel Sanctum):** Proporciona un mecanismo ligero y eficiente para la autenticación basada en tokens, asegurando la seguridad de las APIs.
*   **Sistema de Migraciones:** Permite la gestión versionada del esquema de la base de datos, facilitando el desarrollo colaborativo y la evolución del proyecto.
*   **Maatwebsite/Excel:** Una biblioteca de terceros que integra PHPSpreadsheet, utilizada para la generación y exportación de reportes en formato Excel, demostrando la capacidad de integrar soluciones avanzadas de reporting.

#### Frontend: Vue.js 3 (JavaScript Framework)

**Vue.js** es un framework progresivo de JavaScript para construir interfaces de usuario. Su enfoque en la reactividad y la facilidad de uso lo hacen ideal para aplicaciones de una sola página (SPA). La versión 3, con la Composition API, ofrece mayor flexibilidad y reutilización de lógica. Las razones para su selección incluyen:

*   **Composition API:** Permite organizar la lógica del componente por característica, mejorando la legibilidad y mantenibilidad de grandes componentes.
*   **Reactividad:** Facilita la creación de interfaces de usuario dinámicas que responden automáticamente a los cambios de datos.
*   **Vue Router:** Un sistema de enrutamiento oficial que permite la navegación entre las diferentes vistas del frontend de forma fluida.
*   **Pinia:** El almacén de estado (state management) recomendado para Vue 3, ofreciendo una solución ligera y tipada para la gestión del estado global de la aplicación.
*   **Axios:** Cliente HTTP basado en promesas para el navegador y Node.js, utilizado para realizar peticiones a la API de Laravel.
*   **jsPDF y jspdf-autotable:** Librerías JavaScript utilizadas para la generación de documentos PDF directamente en el lado del cliente, permitiendo la exportación de reportes sin depender exclusivamente del backend para este formato.

#### Base de Datos: MySQL

**MySQL** es un sistema de gestión de bases de datos relacionales (RDBMS) de código abierto, ampliamente utilizado por su fiabilidad, rendimiento y facilidad de uso. Se ha elegido para almacenar toda la información transaccional y maestra del sistema debido a su robustez y su excelente integración con Laravel.

### 3.3 Metodologías de Desarrollo

El proyecto CELUVARIEDADES M y K se ha desarrollado siguiendo principios de metodologías **ágiles**, permitiendo un enfoque iterativo e incremental. Este enfoque facilita la adaptación a nuevos requisitos y la entrega de valor de forma continua. Aunque no se adhirió estrictamente a un framework específico como Scrum, se adoptaron prácticas clave como:

*   **Desarrollo Iterativo:** El proyecto avanzó en ciclos cortos, permitiendo la revisión y adaptación constante.
*   **Flexibilidad ante el Cambio:** Los requisitos y funcionalidades se adaptaron y expandieron a medida que se obtenía una mejor comprensión de las necesidades del negocio.
*   **Entrega Continua:** Se buscó integrar y probar las nuevas funcionalidades de forma regular para asegurar la estabilidad del sistema.
*   **Colaboración:** La naturaleza del desarrollo (frontend/backend) fomentó una estrecha colaboración para asegurar la correcta integración de los componentes.

## 4. Marco Metodológico

El desarrollo del sistema CELUVARIEDADES M y K adoptó un enfoque metodológico que combina elementos del modelo incremental con prácticas ágiles, adaptándose a la naturaleza evolutiva de los requerimientos y a la necesidad de entregar funcionalidades de valor de manera progresiva. La metodología se centró en la iteración, la flexibilidad y la integración continua.

### 4.1 Tipo de Investigación y Enfoque

*   **Tipo de Investigación:** Se clasifica como **investigación aplicada**, ya que busca resolver un problema práctico específico (la optimización de la gestión en CELUVARIEDADES M y K) mediante la creación de una solución tecnológica. También posee un componente **descriptivo** al analizar y detallar las características y el funcionamiento del sistema implementado.
*   **Enfoque de Desarrollo:** Predominantemente **incremental e iterativo**. Esto implicó la construcción del sistema en pequeñas partes funcionales (incrementos), que fueron desarrolladas y probadas en ciclos repetitivos (iteraciones). Cada iteración añadió nuevas funcionalidades o mejoró las existentes, permitiendo retroalimentación temprana y ajustes.

### 4.2 Fases del Proyecto

El proceso de desarrollo se estructuró en las siguientes fases interconectadas y recurrentes:

1.  **Análisis de Requisitos:**
    *   **Inicial:** Partiendo de la propuesta de proyecto original, se identificaron las funcionalidades esenciales para la gestión de ventas, inventario y clientes.
    *   **Evolutivo:** A lo largo del proyecto, los requisitos fueron refinados y expandidos. Se identificaron nuevas necesidades (ej. reportes específicos, gestión de saldos a favor del cliente) y se adaptaron los existentes (ej. el cambio de la entidad `Cartera` por `CuentaPorCobrar` y `SaldoCliente`).
2.  **Diseño:**
    *   **Arquitectura:** Definición de la arquitectura cliente-servidor con API RESTful.
    *   **Diseño de Base de Datos:** Creación y/o adaptación de los modelos de datos (Eloquent) para reflejar las entidades y sus relaciones, incluyendo la reestructuración del módulo de cuentas por cobrar/saldos.
    *   **Diseño de API:** Definición de los endpoints, métodos HTTP, y estructuras de petición/respuesta para la comunicación backend-frontend.
    *   **Diseño UI/UX (Frontend):** Prototipado y diseño de la interfaz de usuario para las diferentes vistas, buscando una experiencia intuitiva y eficiente (ej. `ReportesAdminView.vue`).
3.  **Implementación:**
    *   **Desarrollo Backend:** Construcción de controladores, modelos, rutas, validaciones y lógica de negocio en Laravel. Inclusión de funcionalidades de exportación (ej. `VentasExport`).
    *   **Desarrollo Frontend:** Creación de componentes Vue, servicios de API (ej. `estadisticasService.ts`), gestión de estado (Pinia), enrutamiento (Vue Router), y lógica de interfaz (ej. generación de PDF con `jsPDF`).
    *   **Integración:** Conexión y prueba de la comunicación entre el frontend y el backend.
4.  **Pruebas:**
    *   **Pruebas Unitarias/Integración:** Realizadas durante el desarrollo para verificar el correcto funcionamiento de módulos específicos y su interacción.
    *   **Pruebas Funcionales:** Verificación de que el sistema cumple con los requisitos definidos desde la perspectiva del usuario.
    *   **Depuración:** Identificación y corrección de errores.
5.  **Despliegue y Ajustes:**
    *   Preparación del entorno para el despliegue del sistema.
    *   Realización de ajustes finales basados en las pruebas.

### 4.3 Herramientas y Entorno de Desarrollo

*   **Sistema Operativo:** Linux (Ubuntu/Debian) para el entorno de desarrollo y posible despliegue.
*   **Editor de Código:** Visual Studio Code (VS Code) con extensiones para PHP, JavaScript/TypeScript, Vue.js y Git.
*   **Control de Versiones:** Git, utilizado para el control de versiones del código fuente y la colaboración entre los proyectos de backend y frontend, con repositorios independientes para cada uno.
*   **Gestor de Paquetes PHP:** Composer.
*   **Gestor de Paquetes JavaScript:** pnpm (preferido sobre npm/yarn por su eficiencia en la gestión de dependencias y optimización del espacio en disco).
*   **Servidor Web:** Nginx o Apache (para el entorno de producción).
*   **Entorno de Desarrollo Local:** Laravel Sail (Docker Compose para Laravel) o entornos LAMP/LEMP tradicionales.

Este marco metodológico permitió un desarrollo organizado, adaptable y enfocado en la calidad, facilitando la construcción de un sistema complejo como CELUVARIEDADES M y K.

## 5. Análisis y Diseño del Sistema (Resultados)

El sistema CELUVARIEDADES M y K se ha diseñado como una aplicación web robusta, siguiendo una arquitectura cliente-servidor que facilita la escalabilidad y la mantenibilidad. La implementación se ha realizado con Laravel para el backend y Vue 3 para el frontend, comunicándose a través de una API RESTful.

### 5.1 Arquitectura del Sistema

La arquitectura es de tipo **Monolítica Modular** en el backend, donde Laravel gestiona toda la lógica de negocio, acceso a datos y exposición de servicios API. El frontend es una **Single Page Application (SPA)** construida con Vue 3, que consume estos servicios.

*   **Frontend (Cliente):**
    *   Desarrollado con Vue 3 (Composition API).
    *   Gestiona la interfaz de usuario, la lógica de presentación y la interacción del usuario.
    *   Utiliza Vue Router para la navegación y Pinia para la gestión del estado global.
    *   Se comunica con el backend a través de peticiones HTTP (Axios) a la API RESTful.
    *   Incorpora lógica de generación de reportes PDF (jsPDF) y triggers para exportación a Excel.
*   **Backend (Servidor):**
    *   Desarrollado con Laravel (PHP).
    *   Expone una API RESTful para el consumo del frontend.
    *   Contiene la lógica de negocio, validaciones, gestión de base de datos (Eloquent ORM) y autenticación (Sanctum).
    *   Gestiona la persistencia de datos en MySQL.
    *   Genera reportes complejos y exportaciones a Excel (Maatwebsite/Excel).
*   **Base de Datos:**
    *   MySQL, para el almacenamiento relacional de toda la información del sistema.

Esta separación de responsabilidades permite que cada parte evolucione de manera independiente, mejorando la eficiencia del desarrollo y la especialización de los equipos (aunque en este caso fue un desarrollo integral).

### 5.2 Módulos del Sistema (Inferido Mini-ERP)

El sistema CELUVARIEDADES M y K se estructura en torno a varios módulos clave, que abarcan las principales áreas funcionales de un negocio de comercio y almacén. Estos módulos interactúan entre sí para ofrecer una solución integrada.

#### 5.2.1 Gestión de Usuarios y Seguridad

*   **Usuarios:** CRUD completo de usuarios del sistema, incluyendo roles (ej. administrador, vendedor) y estados (activo/inactivo).
*   **Autenticación:** Sistema de login/logout seguro basado en tokens (Laravel Sanctum).
*   **Autorización:** Control de acceso a funcionalidades específicas según el rol del usuario (implementado a nivel de rutas y controladores).

#### 5.2.2 Gestión de Inventario

*   **Productos:** Registro y gestión de productos (nombre, descripción, precio de compra, precio de venta, stock actual, categoría).
*   **Categorías:** Organización de productos por categorías.
*   **Umbrales de Stock:** Definición de stock mínimo para alertas (ej. `productosBajoStock`).
*   **Movimientos de Inventario:** Registro de entradas y salidas de productos (ventas, compras, ajustes), aunque su implementación explícita como módulo aparte puede variar, se refleja en las operaciones de venta y compra.

#### 5.2.3 Gestión de Ventas

*   **Punto de Venta (POS):** Interfaz ágil para el registro de ventas diarias, búsqueda de productos, cálculo de totales y selección de cliente.
*   **Historial de Ventas:** Consulta detallada de todas las ventas realizadas, con filtros por fecha, cliente, etc.
*   **Devoluciones:** Proceso para registrar devoluciones de productos, afectando inventario y generando saldos a favor o cuentas por cobrar (según la naturaleza de la devolución).
*   **Cuentas por Cobrar:** Gestión de deudas de clientes generadas por ventas a crédito o devoluciones, con registro de abonos (`CuentaPorCobrar` y `AbonoCartera`).
*   **Saldos de Cliente:** Seguimiento de montos a favor del cliente (`SaldoCliente`), generalmente generados por devoluciones o anulaciones, que pueden ser aplicados a futuras compras. Esta entidad reemplazó y expandió la funcionalidad de la entidad `Cartera`.

#### 5.2.4 Gestión de Compras y Proveedores

*   **Proveedores:** Registro y gestión de información de contacto de proveedores.
*   **Pedidos a Proveedores:** Proceso para registrar pedidos de productos a proveedores, incluyendo detalle de ítems y cantidades.
*   **Recepción de Pedidos:** Actualización de inventario tras la recepción de productos de un pedido a proveedor.

#### 5.2.5 Gestión Financiera

*   **Caja Diaria:** Módulo para la apertura y cierre de caja, registro de ingresos y egresos diarios, y cuadre de caja.
*   **Movimientos Financieros:** Registro detallado de otros ingresos y egresos no directamente relacionados con ventas o compras.

#### 5.2.6 Reportes y Estadísticas

Este es un módulo ampliado que proporciona una visión analítica profunda del negocio:

*   **Reportes Existentes (Dashboard):**
    *   **Top Productos Vendidos:** Ranking de los productos más vendidos por cantidad.
    *   **Top Clientes por Monto:** Ranking de clientes con mayor volumen de compra.
    *   **Ventas por Período:** Agregación de ventas totales por día, mes o año.
    *   **Productos Bajo Stock:** Listado de productos con stock por debajo del umbral definido.
    *   **Ticket Promedio:** Valor promedio de las ventas.
    *   **Historial de Ganancias:** Cálculo del margen bruto por período.
*   **Nuevos Reportes Implementados:**
    *   **Reporte de Productos con Baja Rotación:** Identifica productos con pocas o ninguna venta en un período determinado, útil para la gestión de inventario estancado.
        *   **Funcionalidad:** Muestra `ID`, `Nombre Producto`, `Stock Actual`, `Unidades Vendidas en Período` y `Última Venta`.
        *   **Exportación:** Generación de PDF desde el frontend (jsPDF).
    *   **Reporte de Valor Total de Pedidos a Proveedores por Período:** Agrega el monto total gastado en pedidos a proveedores dentro de un rango de fechas, con detalle por proveedor.
    *   **Reporte de Clientes con Mayor Frecuencia de Compra:** Identifica a los clientes que han realizado el mayor número de compras en un período dado.
    *   **Exportación de Ventas Agrupadas a Excel:** Permite descargar un archivo Excel con el detalle de ventas totales y beneficio bruto agrupado por día, mes o año.
        *   **Funcionalidad:** Utiliza el backend (Maatwebsite/Excel) para generar el archivo.

### 5.3 Modelos de Datos Principales

El diseño de la base de datos es relacional, con MySQL como motor, y se gestiona a través de Eloquent ORM en Laravel. A continuación, se describen las entidades más relevantes:

*   **`User`:** Gestión de los usuarios del sistema.
*   **`Cliente`:** Información de los clientes.
*   **`Proveedor`:** Información de los proveedores.
*   **`Producto`:** Detalles de los productos disponibles, incluyendo `stock_actual`.
*   **`Categoria`:** Clasificación de los productos.
*   **`Venta`:** Encabezado de la venta, registrando `cliente_id`, `total`, `estado`, `created_at`.
*   **`DetalleVenta`:** Líneas de detalle de cada venta, enlazadas a `Venta` y `Producto`, con `cantidad`, `precio_unitario` y `precio_costo` (crucial para el cálculo de beneficio).
*   **`PedidoProveedor`:** Encabezado de los pedidos realizados a proveedores, con `proveedor_id` y `total`.
*   **`DetallePedidoProveedor`:** Líneas de detalle de cada pedido a proveedor, enlazadas a `PedidoProveedor` y `Producto`.
*   **`CajaDiaria`:** Registro de aperturas y cierres de caja.
*   **`MovimientoFinanciero`:** Ingresos y egresos no relacionados directamente con ventas o compras.
*   **`CuentaPorCobrar`:** Representa una deuda específica del cliente (reemplazo de `Cartera`), vinculada a una `Venta` o `Devolucion`, con un `monto` y `saldo_pendiente`.
*   **`AbonoCartera`:** Registro de cada pago realizado por el cliente hacia una `CuentaPorCobrar`.
*   **`SaldoCliente`:** Representa un crédito a favor del cliente (ej. por devoluciones o anulaciones), con `monto_original`, `monto_pendiente` y `motivo`. Puede estar vinculado a una `CuentaPorCobrar` si fue generado por una deuda.
*   **`Devolucion`:** Registro de las devoluciones de productos.

#### Evolución en la Gestión de Cartera/Saldos

Una decisión técnica significativa fue la sustitución del concepto monolítico de `Cartera` por un esquema más granular y flexible:

*   **`Cartera` (Obsoleto):** Anteriormente, intentaba manejar las deudas de forma directa.
*   **`CuentaPorCobrar` (Nuevo):** Se enfoca en la deuda específica generada por una transacción (venta a crédito, devolución). Permite una vinculación clara con la transacción origen.
*   **`AbonoCartera` (Nuevo):** Detalla cada pago realizado contra una `CuentaPorCobrar`, proporcionando un historial de pagos transparente.
*   **`SaldoCliente` (Nuevo):** Introduce el concepto de saldo *a favor del cliente*, diferenciándose de las deudas. Esto cubre escenarios como créditos por devoluciones, lo cual no estaba explícitamente modelado antes.

Esta evolución proporciona una gestión financiera más precisa y un historial de transacciones más rico, tanto para el negocio como para el cliente.

### 5.4 Requisitos Funcionales (RFs) y No Funcionales (RNFs)

#### Requisitos Funcionales (RFs)

1.  **RF1: Gestión de Autenticación y Usuarios:**
    *   RF1.1: El sistema permitirá el registro de nuevos usuarios (administradores, vendedores).
    *   RF1.2: Los usuarios podrán iniciar y cerrar sesión de forma segura.
    *   RF1.3: El sistema permitirá la gestión (CRUD) de usuarios existentes.
2.  **RF2: Gestión de Productos e Inventario:**
    *   RF2.1: El sistema permitirá la gestión (CRUD) de productos (nombre, descripción, precios, stock, categoría).
    *   RF2.2: El sistema permitirá la gestión (CRUD) de categorías de productos.
    *   RF2.3: El sistema alertará sobre productos cuyo stock esté por debajo de un umbral definido.
    *   RF2.4: El stock de productos se actualizará automáticamente con cada venta y recepción de pedido.
    *   RF2.5: El sistema identificará y listará productos con baja rotación en un período.
3.  **RF3: Gestión de Ventas (POS y Historial):**
    *   RF3.1: El sistema permitirá registrar ventas rápidamente a través de una interfaz POS.
    *   RF3.2: El sistema permitirá asociar ventas a clientes existentes o registrar ventas genéricas.
    *   RF3.3: El sistema calculará automáticamente los totales de la venta.
    *   RF3.4: El sistema permitirá consultar el historial de ventas con opciones de filtrado.
    *   RF3.5: El sistema permitirá registrar devoluciones de productos.
    *   RF3.6: El sistema gestionará cuentas por cobrar de clientes, permitiendo registrar abonos.
    *   RF3.7: El sistema gestionará saldos a favor del cliente (créditos).
4.  **RF4: Gestión de Compras y Proveedores:**
    *   RF4.1: El sistema permitirá la gestión (CRUD) de proveedores.
    *   RF4.2: El sistema permitirá registrar pedidos a proveedores.
    *   RF4.3: El sistema permitirá registrar la recepción de pedidos a proveedores, actualizando el inventario.
5.  **RF5: Gestión Financiera:**
    *   RF5.1: El sistema permitirá la apertura y cierre de la caja diaria.
    *   RF5.2: El sistema permitirá registrar ingresos y egresos diversos en la caja diaria.
    *   RF5.3: El sistema permitirá consultar un resumen de movimientos financieros.
6.  **RF6: Generación de Reportes y Estadísticas:**
    *   RF6.1: El sistema mostrará un dashboard con KPIs clave (top productos, top clientes, ventas por período, ticket promedio, ganancias).
    *   RF6.2: El sistema generará un reporte de productos bajo stock con opción de exportación a PDF.
    *   RF6.3: El sistema generará un reporte de ventas agrupadas por período con opción de exportación a Excel.
    *   RF6.4: El sistema generará un reporte del valor total de pedidos a proveedores por período.
    *   RF6.5: El sistema generará un reporte de clientes con mayor frecuencia de compra.
7.  **RF7: Notificaciones:**
    *   RF7.1: El sistema notificará sobre eventos críticos (ej. stock bajo).

#### Requisitos No Funcionales (RNFs)

1.  **RNF1: Rendimiento:**
    *   RNF1.1: El sistema responderá a las peticiones de usuario en un tiempo máximo de 3 segundos para el 90% de las operaciones.
    *   RNF1.2: La generación de reportes complejos se completará en menos de 10 segundos.
2.  **RNF2: Seguridad:**
    *   RNF2.1: El sistema protegerá la información sensible mediante autenticación y autorización.
    *   RNF2.2: Todas las comunicaciones entre frontend y backend serán cifradas (HTTPS).
    *   RNF2.3: El sistema será resistente a inyecciones SQL y ataques XSS.
3.  **RNF3: Usabilidad:**
    *   RNF3.1: La interfaz de usuario será intuitiva y fácil de aprender para nuevos usuarios.
    *   RNF3.2: El diseño será responsivo, adaptable a diferentes tamaños de pantalla (escritorio, tablet).
4.  **RNF4: Confiabilidad:**
    *   RNF4.1: El sistema mantendrá la integridad de los datos en todas las transacciones.
    *   RNF4.2: El sistema gestionará errores de forma graciosa, informando al usuario cuando sea necesario.
5.  **RNF5: Mantenibilidad:**
    *   RNF5.1: El código fuente estará bien documentado y seguirá estándares de codificación.
    *   RNF5.2: La arquitectura modular facilitará futuras modificaciones y adiciones de funcionalidades.
6.  **RNF6: Escalabilidad:**
    *   Implementación de pruebas automatizadas en el frontend.

## 7. Referencias APA

*   **Laravel Documentation:** [https://laravel.com/docs/10.x](https://laravel.com/docs/10.x) (Versión 10.x)
*   **Vue.js Documentation:** [https://vuejs.org/guide/](https://vuejs.org/guide/) (Versión 3.x)
*   **Pinia Documentation:** [https://pinia.vuejs.org/](https://pinia.vuejs.org/)
*   **Vue Router Documentation:** [https://router.vuejs.org/](https://router.vuejs.org/)
*   **MySQL Documentation:** [https://dev.mysql.com/doc/](https://dev.mysql.com/doc/)
*   **Maatwebsite Excel Documentation:** [https://docs.laravel-excel.com/](https://docs.laravel-excel.com/)
*   **jsPDF Documentation:** [https://jspdf.org/](https://jspdf.org/)
*   **Maier, R. J., & Hinkel, R. (2007).** *Enterprise Resource Planning (ERP) for Small and Medium-Sized Enterprises*. Springer.
*   **Pressman, R. S., & Lowe, D. (2010).** *Ingeniería del software: Un enfoque práctico* (7a ed.). McGraw-Hill.
*   **APA (American Psychological Association).** (2020). *Publication Manual of the American Psychological Association* (7a ed.).



