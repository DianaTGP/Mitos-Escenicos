# Base de datos Mitos Escénicos

1. En XAMPP, iniciar Apache y MySQL.
2. Crear la base de datos en phpMyAdmin o consola MySQL:
   ```sql
   CREATE DATABASE mitos_escenicos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Editar `config/config.php` con el nombre de la BD, usuario y contraseña de MySQL.
4. Importar `schema.sql` en la base de datos mitos_escenicos (o ejecutar su contenido).
5. Si la base de datos ya existía antes de añadir las columnas `temporada` y `tipo_representacion` a la tabla `obras`, ejecutar también `migrate_obras_temporada.sql`.
6. Para establecer la contraseña del admin a "Admin123!", ejecutar una vez:
   ```php
   php -r "require 'config/config.php'; $h = password_hash('Admin123!', PASSWORD_DEFAULT); $pdo = new PDO(...); $pdo->prepare('UPDATE usuarios SET password_hash=? WHERE rol=?')->execute([$h,'admin']);"
   ```
   O desde el panel admin, usar "Cambiar contraseña" tras iniciar sesión con la temporal.
