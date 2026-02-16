# Instalación en XAMPP (Windows) - Sistema de tickets de consultoría

## Requisitos previos

- XAMPP instalado (con PHP 8.0 o superior, MySQL/MariaDB)
- Navegador web

## Pasos de instalación

### 1. Descomprimir el proyecto

1. Descarga el archivo `ticket-consultoria.zip`
2. Descomprímelo en la carpeta de XAMPP:
   ```
   C:\xampp\htdocs\
   ```
3. Deberías tener la estructura:
   ```
   C:\xampp\htdocs\ticket-consultoria\
   ```

### 2. Iniciar servicios de XAMPP

1. Abre el **Panel de Control de XAMPP**
2. Inicia los servicios:
   - **Apache** (botón Start)
   - **MySQL** (botón Start)

### 3. Crear la base de datos

#### Opción A: Usando phpMyAdmin (recomendado)

1. Abre tu navegador y ve a: `http://localhost/phpmyadmin`
2. Haz clic en la pestaña **"SQL"**
3. Abre el archivo `C:\xampp\htdocs\ticket-consultoria\docs\database.sql` con un editor de texto
4. Copia todo el contenido
5. Pégalo en el área de texto de phpMyAdmin
6. Haz clic en el botón **"Continuar"** o **"Go"**
7. Deberías ver el mensaje de éxito

#### Opción B: Usando línea de comandos

1. Abre **CMD** o **PowerShell**
2. Navega a la carpeta de MySQL de XAMPP:
   ```cmd
   cd C:\xampp\mysql\bin
   ```
3. Ejecuta:
   ```cmd
   mysql.exe -u root -p < C:\xampp\htdocs\ticket-consultoria\docs\database.sql
   ```
4. Presiona Enter (por defecto XAMPP no tiene contraseña para root)

### 4. Configurar la conexión a la base de datos

1. Ve a la carpeta del proyecto:
   ```
   C:\xampp\htdocs\ticket-consultoria\
   ```

2. Copia el archivo `.env.example` y renómbralo a `.env`

3. Abre `.env` con un editor de texto (Notepad++, VSCode, etc.)

4. Configura los valores para XAMPP (generalmente por defecto):
   ```env
   # Configuración de la aplicación
   APP_URL=http://localhost/ticket-consultoria
   APP_ENV=development

   # Configuración de base de datos (valores típicos de XAMPP)
   DB_HOST=localhost
   DB_NAME=ticket_consultoria
   DB_USER=root
   DB_PASS=
   ```
   
   **Nota:** XAMPP por defecto no tiene contraseña para el usuario root, por eso `DB_PASS` está vacío.

### 5. Configurar permisos de carpetas (Windows)

En Windows con XAMPP generalmente no hay problemas de permisos, pero asegúrate de que la carpeta `public/uploads` existe:

1. Verifica que existe:
   ```
   C:\xampp\htdocs\ticket-consultoria\public\uploads\
   ```

2. Si no existe, créala manualmente.

### 6. Acceder a la aplicación

1. Abre tu navegador web
2. Ve a la dirección:
   ```
   http://localhost/ticket-consultoria/public
   ```
   
   O alternativamente:
   ```
   http://localhost/ticket-consultoria/public/index.php
   ```

3. Deberías ser redirigido automáticamente al login

### 7. Iniciar sesión

**Credenciales por defecto:**
- Email: `admin@arelance.com`
- Contraseña: `admin123`

⚠️ **IMPORTANTE:** Cambia esta contraseña inmediatamente después del primer acceso.

## Configuración opcional: VirtualHost

Para acceder con una URL más limpia (ejemplo: `http://tickets.local` en lugar de `http://localhost/ticket-consultoria/public`):

### 1. Editar archivo hosts

1. Abre como **Administrador**:
   ```
   C:\Windows\System32\drivers\etc\hosts
   ```

2. Añade al final:
   ```
   127.0.0.1    tickets.local
   ```

3. Guarda el archivo

### 2. Configurar VirtualHost en Apache

1. Abre el archivo de configuración de VirtualHosts:
   ```
   C:\xampp\apache\conf\extra\httpd-vhosts.conf
   ```

2. Añade al final:
   ```apache
   <VirtualHost *:80>
       ServerName tickets.local
       DocumentRoot "C:/xampp/htdocs/ticket-consultoria/public"
       
       <Directory "C:/xampp/htdocs/ticket-consultoria/public">
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

3. Asegúrate de que el archivo principal de Apache incluye los VirtualHosts. Abre:
   ```
   C:\xampp\apache\conf\httpd.conf
   ```
   
4. Busca esta línea y asegúrate de que NO esté comentada (sin #):
   ```apache
   Include conf/extra/httpd-vhosts.conf
   ```

5. Reinicia Apache desde el Panel de Control de XAMPP

6. Ahora puedes acceder con:
   ```
   http://tickets.local
   ```

## Solución de problemas comunes

### Error: "Page not found" o 404

**Solución:** Asegúrate de acceder a la carpeta `public`:
```
http://localhost/ticket-consultoria/public
```

### Error: "Could not connect to database"

**Posibles causas:**

1. **MySQL no está iniciado**
   - Verifica en el Panel de XAMPP que MySQL está corriendo (botón verde)

2. **Credenciales incorrectas**
   - Verifica el archivo `.env`:
     ```env
     DB_HOST=localhost
     DB_NAME=ticket_consultoria
     DB_USER=root
     DB_PASS=
     ```

3. **La base de datos no se creó**
   - Ve a phpMyAdmin: `http://localhost/phpmyadmin`
   - Verifica que existe la base de datos `ticket_consultoria`
   - Si no existe, ejecuta de nuevo el script `database.sql`

### Error: "Session failed" o problemas con sesiones

**Solución:** Verifica que la carpeta de sesiones de PHP existe:
```
C:\xampp\tmp\
```

Si no existe, créala.

### Los estilos no cargan (página sin CSS)

**Solución:** Verifica que estás accediendo a través de la carpeta `public`:
```
http://localhost/ticket-consultoria/public
```

### Error 500 - Internal Server Error

**Solución:**

1. Activa la visualización de errores editando:
   ```
   C:\xampp\htdocs\ticket-consultoria\app\bootstrap.php
   ```
   
2. Verifica que estas líneas estén al principio:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

3. Recarga la página y verás el error específico

### Advertencia: "date_default_timezone_set()"

**Solución:** Ya está configurado en el código, pero si persiste, edita:
```
C:\xampp\php\php.ini
```

Busca y descomenta (quita el ;):
```ini
date.timezone = Europe/Madrid
```

Reinicia Apache.

## Prueba rápida

Para verificar que todo funciona:

1. Accede a: `http://localhost/ticket-consultoria/public`
2. Inicia sesión con las credenciales de admin
3. Deberías ver el dashboard
4. Intenta crear un nuevo usuario desde "Administración > Usuarios"
5. Cierra sesión e inicia con el nuevo usuario
6. Crea un ticket de prueba

## Recursos adicionales

- **Panel de Control XAMPP:** Para iniciar/detener servicios
- **phpMyAdmin:** `http://localhost/phpmyadmin` para gestionar la base de datos
- **Logs de errores de Apache:** `C:\xampp\apache\logs\error.log`
- **Logs de PHP:** `C:\xampp\php\logs\php_error_log`

## Desinstalación

Para eliminar la aplicación:

1. Detén los servicios de XAMPP
2. Elimina la carpeta:
   ```
   C:\xampp\htdocs\ticket-consultoria\
   ```
3. Elimina la base de datos desde phpMyAdmin:
   - Selecciona `ticket_consultoria`
   - Click en "Eliminar"

## Próximos pasos

Una vez que la aplicación funcione correctamente en local:

1. Cambia la contraseña del administrador
2. Crea usuarios de prueba (consultores y clientes)
3. Prueba el flujo completo de creación de tickets
4. Familiarízate con todas las funcionalidades
5. Cuando estés listo para producción, consulta el `README.md` para despliegue en servidor real

---

**Soporte:** Para más información consulta `README.md` o `INICIO-RAPIDO.md`
