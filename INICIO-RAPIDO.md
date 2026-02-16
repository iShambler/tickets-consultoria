# Guía rápida de inicio - Sistema de tickets de consultoría

## Instalación rápida (5 minutos)

### Opción 1: Instalación automática

```bash
cd ticket-consultoria
sudo bash install.sh
```

Sigue las instrucciones en pantalla.

### Opción 2: Instalación manual

1. **Crear base de datos:**
```bash
mysql -u root -p < docs/database.sql
```

2. **Configurar variables de entorno:**
```bash
cp .env.example .env
nano .env  # Editar credenciales
```

3. **Configurar permisos:**
```bash
chmod -R 775 public/uploads
chown -R www-data:www-data public/uploads
```

4. **Configurar Apache:**
Apuntar DocumentRoot a la carpeta `public/`

5. **Acceder:**
http://tu-dominio

**Login inicial:**
- Email: admin@arelance.com
- Contraseña: admin123

⚠️ Cambiar contraseña inmediatamente.

## Estructura de archivos principales

```
ticket-consultoria/
├── app/
│   ├── models/          # Usuario.php, Ticket.php
│   ├── utils/           # Database.php, Validator.php, Auth.php
│   └── config/          # app.php, database.php
├── public/              # ← DocumentRoot de Apache
│   ├── login.php
│   ├── dashboard.php
│   └── ticket.php
├── docs/
│   └── database.sql     # Script de BD
└── README.md            # Documentación completa
```

## Flujo de uso

### Cliente:
1. Registro → 2. Crear ticket → 3. Seguimiento → 4. Comentar

### Consultor:
1. Ver tickets asignados → 2. Registrar tiempo → 3. Actualizar estado

### Admin:
1. Asignar tickets → 2. Gestionar usuarios → 3. Ver reportes

## Configuración importante

### Base de datos
Archivo: `app/config/database.php` o `.env`
```env
DB_HOST=localhost
DB_NAME=ticket_consultoria
DB_USER=tu_usuario
DB_PASS=tu_contraseña
```

### Email (opcional)
Archivo: `.env`
```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=tu-email@gmail.com
SMTP_PASS=contraseña-de-aplicación
```

### Seguridad
- Cambiar contraseña admin
- Activar HTTPS en producción
- Configurar backups automáticos
- Revisar permisos de archivos

## Problemas comunes

**Error 500:**
- Revisar logs: `/var/log/apache2/error.log`
- Verificar permisos de `public/uploads`
- Activar display_errors en desarrollo

**No conecta a BD:**
- Verificar credenciales en `.env`
- Verificar que MySQL está activo: `systemctl status mysql`

**Sesiones no funcionan:**
- Verificar permisos: `chmod 733 /var/lib/php/sessions`

## Siguientes pasos

1. Cambiar contraseña del admin
2. Crear usuarios consultores
3. Configurar tipos de consultoría
4. Probar creación de ticket
5. Configurar emails (opcional)
6. Configurar backups
7. Revisar documentación completa en README.md

## Soporte

Documentación completa: `README.md`
Tests: `php tests/ValidatorTest.php`

---

Desarrollado para Arelance - 2026
