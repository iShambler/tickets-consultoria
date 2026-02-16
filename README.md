# Sistema de tickets de consultoría

Sistema web de gestión de tickets de consultoría desarrollado en PHP con arquitectura MVC, diseñado para facilitar la comunicación entre clientes y consultores de Arelance.

## Características principales

- ✅ Gestión completa de tickets (crear, actualizar, comentar, cerrar)
- ✅ Tres roles de usuario: administrador, consultor y cliente
- ✅ Sistema de autenticación y sesiones seguro
- ✅ Seguimiento de tiempo invertido por consultor
- ✅ Adjuntar archivos a los tickets
- ✅ Historial completo de cambios
- ✅ Comentarios internos y públicos
- ✅ Filtros y búsqueda avanzada
- ✅ Dashboard con estadísticas
- ✅ Interfaz responsive con Bootstrap 5

## Requisitos técnicos

- PHP 8.0 o superior
- MySQL 5.7 / MariaDB 10.3 o superior
- Apache 2.4 con mod_rewrite
- Extensiones PHP requeridas:
  - PDO
  - pdo_mysql
  - mbstring
  - fileinfo
  - session

## Instalación

### 1. Clonar o descargar el proyecto

```bash
cd /var/www/html
git clone [url-del-repositorio] ticket-consultoria
cd ticket-consultoria
```

### 2. Configurar permisos

```bash
# Dar permisos de escritura a directorios necesarios
chmod -R 775 public/uploads
chown -R www-data:www-data public/uploads

# Si hay problemas de permisos con sesiones
chmod -R 775 /var/lib/php/sessions
```

### 3. Crear la base de datos

```bash
# Acceder a MySQL
mysql -u root -p

# Ejecutar el script de creación
mysql -u root -p < docs/database.sql
```

O importar manualmente el archivo `docs/database.sql` desde phpMyAdmin.

### 4. Configurar variables de entorno

```bash
# Copiar el archivo de ejemplo
cp .env.example .env

# Editar con tus datos
nano .env
```

Configurar especialmente:
- Credenciales de base de datos
- Configuración SMTP para envío de emails
- URL de la aplicación

### 5. Configurar Apache

Crear un VirtualHost o configurar el DocumentRoot apuntando a la carpeta `public`:

```apache
<VirtualHost *:80>
    ServerName tickets.tuempresa.com
    DocumentRoot /var/www/html/ticket-consultoria/public
    
    <Directory /var/www/html/ticket-consultoria/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/tickets-error.log
    CustomLog ${APACHE_LOG_DIR}/tickets-access.log combined
</VirtualHost>
```

Reiniciar Apache:

```bash
sudo systemctl restart apache2
```

### 6. Acceder a la aplicación

Abrir en el navegador:
```
http://tickets.tuempresa.com
```

**Usuario administrador por defecto:**
- Email: `admin@arelance.com`
- Contraseña: `admin123`

⚠️ **IMPORTANTE:** Cambiar esta contraseña inmediatamente después del primer acceso.

## Estructura del proyecto

```
ticket-consultoria/
├── app/
│   ├── config/          # Archivos de configuración
│   ├── controllers/     # Controladores (lógica de negocio)
│   ├── models/          # Modelos (acceso a datos)
│   ├── views/           # Vistas (plantillas HTML)
│   ├── middleware/      # Middleware de autenticación/autorización
│   ├── utils/           # Clases de utilidad
│   ├── autoload.php     # Autoloader PSR-4
│   └── bootstrap.php    # Inicialización de la aplicación
├── public/              # Directorio público (DocumentRoot)
│   ├── css/            # Estilos CSS
│   ├── js/             # JavaScript
│   ├── uploads/        # Archivos subidos
│   ├── index.php       # Punto de entrada
│   ├── login.php       # Página de login
│   ├── dashboard.php   # Dashboard principal
│   └── .htaccess       # Configuración Apache
├── docs/               # Documentación
│   └── database.sql    # Script de base de datos
├── tests/              # Tests unitarios
├── .env.example        # Ejemplo de variables de entorno
├── .gitignore          # Archivos ignorados por Git
└── README.md           # Este archivo
```

## Uso del sistema

### Para clientes

1. **Registro**: Crear cuenta desde la página de registro
2. **Crear ticket**: Desde el dashboard, botón "Nuevo ticket"
3. **Seguimiento**: Ver estado de tickets en "Mis tickets"
4. **Comentar**: Añadir comentarios y adjuntar archivos
5. **Notificaciones**: Recibir emails cuando cambie el estado

### Para consultores

1. **Ver tickets asignados**: Dashboard muestra tickets asignados
2. **Registrar tiempo**: Añadir horas trabajadas en cada ticket
3. **Actualizar estado**: Cambiar estado según progreso
4. **Comentarios internos**: Notas visibles solo para staff
5. **Gestionar prioridades**: Clasificar tickets por urgencia

### Para administradores

1. **Asignar tickets**: Asignar consultores a tickets
2. **Gestionar usuarios**: Crear y administrar cuentas
3. **Configurar tipos**: Definir tipos de consultoría
4. **Reportes**: Ver estadísticas y métricas
5. **Administración completa**: Acceso total al sistema

## Seguridad

El sistema implementa:

- ✅ Contraseñas hasheadas con bcrypt
- ✅ Protección contra CSRF
- ✅ Validación de datos de entrada
- ✅ Sesiones seguras con configuración estricta
- ✅ Protección contra inyección SQL (PDO preparado)
- ✅ Escape de salida HTML (XSS)
- ✅ Control de acceso basado en roles
- ✅ Validación de archivos subidos
- ✅ Headers de seguridad HTTP

## Testing

Los tests se encuentran en la carpeta `tests/`. Para ejecutarlos:

```bash
# Instalar PHPUnit (si no está instalado)
composer require --dev phpunit/phpunit

# Ejecutar tests
./vendor/bin/phpunit tests/
```

## Mantenimiento

### Backup de base de datos

```bash
# Backup completo
mysqldump -u root -p ticket_consultoria > backup_$(date +%Y%m%d).sql

# Restaurar backup
mysql -u root -p ticket_consultoria < backup_20260202.sql
```

### Limpiar archivos antiguos

Crear un cron job para limpiar archivos de tickets cerrados después de X días:

```bash
# Editar crontab
crontab -e

# Añadir limpieza semanal (domingos a las 3 AM)
0 3 * * 0 find /var/www/html/ticket-consultoria/public/uploads -mtime +365 -type f -delete
```

### Logs

Los logs se guardan en:
- Logs de Apache: `/var/log/apache2/`
- Logs de PHP: Configurar en `php.ini`
- Logs de aplicación: Usar `error_log()` en el código

## Personalización

### Cambiar logo y colores

Editar `public/css/style.css` y variables CSS en `:root`.

### Modificar tipos de consultoría

Acceder como administrador a "Tipos de consultoría" o editar directamente en la base de datos tabla `tipos_consultoria`.

### Configurar notificaciones email

Editar `app/config/app.php` sección `mail` o variables de entorno en `.env`.

## Solución de problemas

### Error de conexión a base de datos

Verificar credenciales en `.env` o `app/config/database.php`.

### Permisos de archivos

```bash
# Resetear permisos
sudo chown -R www-data:www-data /var/www/html/ticket-consultoria
sudo chmod -R 755 /var/www/html/ticket-consultoria
sudo chmod -R 775 public/uploads
```

### Error 500

Revisar logs de Apache y activar display_errors en desarrollo:

```php
// En app/bootstrap.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Sesiones no persisten

Verificar permisos en directorio de sesiones:

```bash
sudo chmod 733 /var/lib/php/sessions
sudo chown root:www-data /var/lib/php/sessions
```

## Contribuir

1. Fork del proyecto
2. Crear rama para feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit de cambios (`git commit -am 'Añadir nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## Licencia

Proyecto propietario de Arelance. Todos los derechos reservados.

## Soporte

Para soporte técnico contactar a:
- Email: soporte@arelance.com
- Documentación: https://docs.arelance.com/tickets

## Changelog

### Versión 1.0.0 (2026-02-02)
- ✅ Release inicial
- ✅ Sistema completo de tickets
- ✅ Gestión de usuarios
- ✅ Dashboard y reportes básicos
- ✅ Sistema de comentarios y archivos adjuntos
- ✅ Seguimiento de tiempo

## Próximas características

- [ ] Notificaciones email automáticas
- [ ] API REST para integraciones
- [ ] Aplicación móvil
- [ ] Chat en tiempo real
- [ ] Reportes avanzados y gráficas
- [ ] Integración con calendario
- [ ] Sistema de plantillas de tickets
- [ ] Exportación PDF de tickets
- [ ] Multi-idioma
