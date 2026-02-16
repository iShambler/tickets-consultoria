# API de Tickets - Documentación

## Base URL
```
http://localhost/ticket-consultoria/public/api
```

## Autenticación
Todas las peticiones requieren una API Key en el header:
```
X-API-KEY: tu_api_key_aqui
```

También acepta: `Authorization: Bearer tu_api_key_aqui`

---

## Endpoints

### POST /tickets/create.php
Crea un nuevo ticket desde un sistema externo (n8n, email, etc.)

**Headers:**
| Header | Valor | Obligatorio |
|--------|-------|-------------|
| Content-Type | application/json | Sí |
| X-API-KEY | tu_api_key | Sí |

**Body (JSON):**
| Campo | Tipo | Obligatorio | Descripción |
|-------|------|-------------|-------------|
| email | string | ✅ | Email del cliente |
| titulo | string | ✅ | Título del ticket (max 255) |
| descripcion | string | ✅ | Descripción del problema |
| nombre | string | No | Nombre del cliente |
| categoria | string | No | hardware/software/red/servidor/desarrollo/formacion/otro |
| prioridad | string | No | baja/media/alta/critica (default: media) |
| departamento | string | No | Departamento del cliente |
| urgencia_keywords | array | No | Palabras clave de urgencia detectadas por IA |
| resumen_ia | string | No | Resumen generado por IA |
| email_subject | string | No | Asunto original del email |
| email_body_original | string | No | Cuerpo original del email |
| email_message_id | string | No | Message-ID del email (para evitar duplicados) |
| metadata | object | No | Datos extra (email_date, ip_origen, etc.) |

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "data": {
    "ticket_id": 5,
    "ticket_numero": "TICK-2026-0005",
    "cliente_id": 12,
    "tipo_consultoria_id": 3,
    "prioridad": "alta",
    "estado": "nuevo",
    "fuente": "email",
    "created_at": "2026-02-12T10:30:00+01:00"
  },
  "timestamp": "2026-02-12T10:30:00+01:00"
}
```

**Errores posibles:**
| Código | Significado |
|--------|-------------|
| 400 | JSON inválido o cuerpo vacío |
| 401 | API Key no proporcionada o inválida |
| 403 | API Key sin permisos suficientes |
| 405 | Método HTTP no permitido (solo POST) |
| 422 | Error de validación (campos faltantes, email inválido) |
| 429 | Rate limit excedido (máx 60 req/min) |
| 500 | Error interno del servidor |

---

## Ejemplos

### curl básico
```bash
curl -X POST http://localhost/ticket-consultoria/public/api/tickets/create.php \
  -H "X-API-KEY: tu_api_key" \
  -H "Content-Type: application/json" \
  -d '{"email":"juan@empresa.com","titulo":"No funciona internet","descripcion":"Se ha caído la conexión"}'
```

### curl completo (como enviaría n8n)
```bash
curl -X POST http://localhost/ticket-consultoria/public/api/tickets/create.php \
  -H "X-API-KEY: tu_api_key" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "pedro@empresa.com",
    "nombre": "Pedro García",
    "titulo": "No puedo acceder al servidor de archivos",
    "descripcion": "Desde esta mañana no puedo conectarme. Error timeout.",
    "categoria": "red",
    "prioridad": "alta",
    "departamento": "Contabilidad",
    "urgencia_keywords": ["no puedo", "esta mañana"],
    "resumen_ia": "Usuario no puede acceder a servidor de archivos por timeout",
    "email_subject": "RE: Problema con servidor",
    "email_body_original": "Buenos días, desde esta mañana no puedo...",
    "metadata": {
      "email_date": "2026-02-12T09:15:00Z",
      "ip_origen": "192.168.1.50"
    }
  }'
```

### PowerShell
```powershell
$headers = @{
    "Content-Type" = "application/json"
    "X-API-KEY" = "tu_api_key"
}

$body = @{
    email = "test@test.com"
    titulo = "Prueba desde PowerShell"
    descripcion = "Esto es una prueba"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost/ticket-consultoria/public/api/tickets/create.php" -Method POST -Headers $headers -Body $body
```

---

## Mapeo de Categorías
La IA clasifica en categorías que se mapean automáticamente:

| Categoría IA | → Tipo Consultoría |
|-------------|-------------------|
| hardware | Soporte técnico |
| software | Soporte técnico |
| red | Infraestructura |
| servidor | Infraestructura |
| desarrollo | Desarrollo Moodle |
| formacion | Formación |
| otro | Otro |

---

## Rate Limiting
- Máximo 60 peticiones por minuto por IP
- Las peticiones que excedan el límite recibirán un error 429

---

## Gestión de API Keys
- **Web:** http://localhost/ticket-consultoria/public/api-keys.php (requiere login admin)
- **CLI:** `php docs/GENERAR-API-KEY.php`

## Logs
Todas las peticiones se registran en la tabla `api_logs`. Consultables desde:
- Panel web de API Keys (últimas 20)
- SQL: `SELECT * FROM api_logs ORDER BY created_at DESC`
