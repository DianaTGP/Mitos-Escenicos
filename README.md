# Mitos Escénicos – Aplicación web

Aplicación web para la compañía de teatro **Mitos Escénicos**: cartelera, tienda, compra de boletos y mercancía, pagos con Openpay.

## Requisitos

- PHP 7.4+ (o 8.x) con extensiones: pdo_mysql, json, session, mbstring
- MySQL 5.7+ (XAMPP incluye MariaDB)
- Servidor web (Apache en XAMPP) con document root apuntando a la carpeta del proyecto

## Instalación

1. **Clonar o copiar** el proyecto en la carpeta que use el servidor (por ejemplo `htdocs/mitos_escenicos` en XAMPP).

2. **Base de datos**
   - Iniciar MySQL en XAMPP.
   - Crear la base de datos: `CREATE DATABASE mitos_escenicos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
   - Importar el esquema: ejecutar el contenido de `sql/schema.sql` en la base `mitos_escenicos` (phpMyAdmin o línea de comandos).

3. **Configuración**
   - Copiar o editar `config/config.php` y definir las constantes de conexión a MySQL: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.
   - Si la aplicación no está en la raíz del dominio, definir `MITOS_BASE_URL` (por ejemplo `/mitos_escenicos`) en el servidor o en `config/config.php`.
   - Para pagos con Openpay: definir `OPENPAY_MERCHANT_ID`, `OPENPAY_PRIVATE_KEY`, `OPENPAY_PUBLIC_KEY` y `OPENPAY_SANDBOX` (true en desarrollo). Sin estas claves, el flujo de pago no procesará cobros reales.

4. **Usuario administrador**
   - El script `schema.sql` inserta un usuario admin con email `admin@mitosescenicos.com`. La contraseña por defecto del hash incluido suele ser `password`; cámbiala desde **Mi perfil** en el panel admin tras el primer acceso, o genera un nuevo hash con `password_hash('TuClave', PASSWORD_DEFAULT)` en PHP y actualiza la tabla `usuarios`.

## Estructura principal

- `config/config.php` – Configuración y conexión PDO.
- `php/` – Auth, layout, helpers Openpay.
- `api/` – Endpoints para carrito y pago (Openpay).
- `admin/` – Panel de administración (solo rol admin).
- `sql/schema.sql` – Creación de tablas y usuario admin inicial.
- `css/estilos.css` – Estilos unificados.
- `js/carrito.js` – Ayudas para el carrito en la tienda.

## Uso

- **Público:** Inicio, Cartelera, Obra (detalle y funciones), Tienda, Carrito, Pago (requiere sesión), Confirmación, Mis compras, Mis boletos, Sobre nosotros.
- **Usuario registrado:** Login, Registro, Perfil (editar datos y contraseña), compra de boletos y mercancía, pago con Openpay (opción de guardar tarjeta sin CVV).
- **Administrador (una sola cuenta):** Panel admin para agregar/editar/eliminar obras, habilitar o deshabilitar venta de boletos por obra, gestionar funciones, lugares y mercancía.

## Seguridad

- Contraseñas con `password_hash` / `password_verify`.
- Consultas con prepared statements (PDO).
- Tarjetas procesadas con Openpay (tokenización); no se almacenan número completo ni CVV.
- Sesiones con cookie httponly y regeneración de ID tras login.

## Openpay

- Modo sandbox: `OPENPAY_SANDBOX = true` y claves de prueba.
- Producción: claves reales y `OPENPAY_SANDBOX = false`. Usar HTTPS.
