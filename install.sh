#!/bin/bash

###############################################################################
# Script de instalación rápida del Sistema de Tickets de Consultoría
# Uso: sudo bash install.sh
###############################################################################

set -e

echo "=========================================="
echo "Sistema de Tickets de Consultoría"
echo "Instalación automática"
echo "=========================================="
echo ""

# Verificar que se ejecuta como root
if [ "$EUID" -ne 0 ]; then 
    echo "❌ Este script debe ejecutarse como root (sudo)"
    exit 1
fi

# Colores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # Sin color

# Función para imprimir mensajes
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

# 1. Verificar requisitos del sistema
echo "1. Verificando requisitos del sistema..."

# Verificar PHP
if ! command -v php &> /dev/null; then
    print_error "PHP no está instalado"
    echo "Instalar con: sudo apt install php php-mysql php-mbstring php-xml"
    exit 1
fi

PHP_VERSION=$(php -r 'echo PHP_VERSION;')
print_success "PHP $PHP_VERSION instalado"

# Verificar MySQL
if ! command -v mysql &> /dev/null; then
    print_warning "MySQL/MariaDB no detectado"
    echo "Instalar con: sudo apt install mysql-server"
fi

# 2. Configurar permisos
echo ""
echo "2. Configurando permisos..."

chown -R www-data:www-data .
chmod -R 755 .
chmod -R 775 public/uploads
print_success "Permisos configurados"

# 3. Crear archivo .env
echo ""
echo "3. Configurando variables de entorno..."

if [ ! -f .env ]; then
    cp .env.example .env
    print_success "Archivo .env creado desde .env.example"
    print_warning "Edita .env con tus credenciales de base de datos"
else
    print_warning ".env ya existe, no se sobrescribirá"
fi

# 4. Configuración de base de datos
echo ""
echo "4. ¿Deseas crear la base de datos ahora? (s/n)"
read -r crear_bd

if [ "$crear_bd" = "s" ] || [ "$crear_bd" = "S" ]; then
    echo "Introduce el usuario root de MySQL:"
    read -r mysql_user
    
    echo "Introduce la contraseña de MySQL:"
    read -rs mysql_pass
    
    echo ""
    echo "Creando base de datos..."
    
    mysql -u "$mysql_user" -p"$mysql_pass" < docs/database.sql
    
    if [ $? -eq 0 ]; then
        print_success "Base de datos creada exitosamente"
    else
        print_error "Error al crear la base de datos"
        print_warning "Puedes hacerlo manualmente: mysql -u root -p < docs/database.sql"
    fi
else
    print_warning "Recuerda crear la base de datos manualmente:"
    echo "  mysql -u root -p < docs/database.sql"
fi

# 5. Configurar Apache (opcional)
echo ""
echo "5. ¿Deseas configurar Apache VirtualHost? (s/n)"
read -r config_apache

if [ "$config_apache" = "s" ] || [ "$config_apache" = "S" ]; then
    echo "Introduce el nombre del dominio (ejemplo: tickets.local):"
    read -r domain_name
    
    echo "Introduce la ruta completa del proyecto (ejemplo: /var/www/html/ticket-consultoria):"
    read -r project_path
    
    # Crear VirtualHost
    cat > /etc/apache2/sites-available/tickets.conf <<EOF
<VirtualHost *:80>
    ServerName $domain_name
    DocumentRoot $project_path/public
    
    <Directory $project_path/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/tickets-error.log
    CustomLog \${APACHE_LOG_DIR}/tickets-access.log combined
</VirtualHost>
EOF
    
    # Habilitar módulos necesarios
    a2enmod rewrite
    a2enmod headers
    
    # Habilitar sitio
    a2ensite tickets.conf
    
    # Reiniciar Apache
    systemctl restart apache2
    
    print_success "VirtualHost configurado para $domain_name"
    print_warning "Añade '$domain_name' a /etc/hosts si es desarrollo local"
    echo "  127.0.0.1  $domain_name"
else
    print_warning "Recuerda configurar Apache manualmente apuntando a public/"
fi

# 6. Test de instalación
echo ""
echo "6. Ejecutando tests básicos..."

if php tests/ValidatorTest.php &> /dev/null; then
    print_success "Tests básicos pasados"
else
    print_warning "Algunos tests fallaron, revisa manualmente"
fi

# Resumen final
echo ""
echo "=========================================="
echo "Instalación completada"
echo "=========================================="
echo ""
print_success "El sistema está instalado"
echo ""
echo "Próximos pasos:"
echo "1. Editar .env con tus credenciales"
echo "2. Acceder a http://tu-dominio"
echo "3. Login con:"
echo "   Email: admin@arelance.com"
echo "   Contraseña: admin123"
echo ""
print_warning "IMPORTANTE: Cambia la contraseña del administrador"
echo ""
echo "Documentación completa en README.md"
echo ""
