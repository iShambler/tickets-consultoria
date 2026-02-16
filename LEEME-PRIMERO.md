# 🚀 Instalación rápida en XAMPP (3 pasos)

## ✅ Paso 1: Descomprimir
```
Descomprimir ticket-consultoria.zip en:
C:\xampp\htdocs\

Resultado:
C:\xampp\htdocs\ticket-consultoria\
```

## ✅ Paso 2: Crear base de datos

### Método fácil (phpMyAdmin):
1. Abrir: `http://localhost/phpmyadmin`
2. Click pestaña **"SQL"**
3. Abrir archivo: `C:\xampp\htdocs\ticket-consultoria\docs\database.sql`
4. Copiar todo el contenido
5. Pegar en phpMyAdmin
6. Click **"Continuar"**

## ✅ Paso 3: Acceder

Abrir navegador:
```
http://localhost/ticket-consultoria/public
```

**Login:**
- 📧 Email: `admin@arelance.com`
- 🔑 Contraseña: `admin123`

---

## 🔧 Configuración automática (opcional)

Ejecutar el archivo:
```
C:\xampp\htdocs\ticket-consultoria\instalar-xampp.bat
```

Sigue las instrucciones en pantalla.

---

## ❌ ¿Problemas?

### No conecta a la base de datos
1. Verifica que MySQL esté iniciado en el Panel de XAMPP
2. Verifica que creaste la base de datos (paso 2)

### Página en blanco o error 500
1. Asegúrate de acceder a: `http://localhost/ticket-consultoria/public`
2. Verifica que Apache está iniciado en XAMPP

### No encuentra archivos CSS
Accede a la carpeta `public`:
```
http://localhost/ticket-consultoria/public
```

---

## 📚 Documentación completa

Para más detalles, ver:
- `INSTALACION-XAMPP.md` - Guía completa paso a paso
- `README.md` - Documentación técnica completa
- `INICIO-RAPIDO.md` - Guía de inicio rápido

---

## 🎯 Siguiente paso

Una vez instalado:
1. ✅ Cambia la contraseña del admin
2. ✅ Crea un usuario consultor
3. ✅ Crea un usuario cliente
4. ✅ Prueba crear un ticket

¡Listo! 🎉
