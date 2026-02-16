<?php
/**
 * Controlador de Administración
 * Usuarios, Tipos de consultoría, Reportes, API Keys
 */

namespace App\Controllers;

use App\Models\Usuario;
use App\Utils\Auth;
use App\Utils\Database;
use App\Utils\ApiAuth;
use App\Middleware\AuthMiddleware;

class AdminController extends BaseController
{
    /**
     * GET/POST /usuarios.php - Gestión de usuarios
     */
    public function usuarios(): void
    {
        AuthMiddleware::requireAdminOrSistemas();

        $mensaje = null;
        $mensajeTipo = null;
        $editando = null;
        $errores = [];

        // Procesar acciones POST
        if ($this->isPost()) {
            $action = $this->postData('action', '');

            switch ($action) {
                case 'create':
                    $errores = $this->crearUsuario();
                    if (empty($errores)) {
                        $mensajeTipo = 'success';
                        $mensaje = 'Usuario creado correctamente';
                    }
                    break;

                case 'update':
                    $errores = $this->actualizarUsuario();
                    if (empty($errores)) {
                        $mensajeTipo = 'success';
                        $mensaje = 'Usuario actualizado correctamente';
                    }
                    break;

                case 'toggle':
                    $id = (int) $this->postData('id', 0);
                    $activo = (int) $this->postData('activo', 0);
                    if ($id && $id !== Auth::id()) {
                        Database::update('usuarios', ['activo' => $activo], 'id = ?', [$id]);
                        $mensajeTipo = 'success';
                        $mensaje = $activo ? 'Usuario activado' : 'Usuario desactivado';
                    } else {
                        $mensajeTipo = 'danger';
                        $mensaje = 'No puedes desactivarte a ti mismo';
                    }
                    break;

                case 'change_role':
                    $id = (int) $this->postData('id', 0);
                    $nuevoRol = (int) $this->postData('rol', 3);
                    if ($id && $id !== Auth::id() && in_array($nuevoRol, [1, 2, 3, 4])) {
                        Database::update('usuarios', ['rol' => $nuevoRol], 'id = ?', [$id]);
                        $mensajeTipo = 'success';
                        $roles = [1 => 'Administrador', 2 => 'Consultor', 3 => 'Cliente', 4 => 'Sistemas'];
                        $mensaje = 'Rol cambiado a ' . $roles[$nuevoRol];
                    } elseif ($id === Auth::id()) {
                        $mensajeTipo = 'danger';
                        $mensaje = 'No puedes cambiar tu propio rol';
                    }
                    break;

                case 'reset_password':
                    $id = (int) $this->postData('id', 0);
                    $newPass = $this->postData('new_password', '');
                    if ($id && strlen($newPass) >= 6) {
                        $usuario = Usuario::findById($id);
                        if ($usuario) {
                            $usuario->updatePassword($newPass);
                            $mensajeTipo = 'success';
                            $mensaje = 'Contraseña restablecida para ' . $usuario->getNombre();
                        }
                    } else {
                        $mensajeTipo = 'danger';
                        $mensaje = 'La contraseña debe tener al menos 6 caracteres';
                    }
                    break;
            }
        }

        // Si viene ?edit=ID, cargar usuario para editar
        $editId = (int) ($this->queryData('edit', 0));
        if ($editId) {
            $editando = Usuario::findById($editId);
        }

        $allUsuarios = Usuario::getAll(null, false);
        $pagUsuarios = paginarArray($allUsuarios, (int)($_GET['page'] ?? 1), (int)($_GET['per_page'] ?? 10));

        // Stats sobre todos los usuarios (no paginados)
        $userStats = ['total' => count($allUsuarios), 'activos' => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0];
        foreach ($allUsuarios as $u) {
            $userStats[$u->getRol()] = ($userStats[$u->getRol()] ?? 0) + 1;
            if ($u->isActivo()) $userStats['activos']++;
        }

        $this->renderWithLayout('admin/usuarios', [
            'pageTitle' => 'Usuarios',
            'usuarios' => $pagUsuarios['items'],
            'pagUsuarios' => $pagUsuarios['paginacion'],
            'userStats' => $userStats,
            'mensaje' => $mensaje,
            'mensajeTipo' => $mensajeTipo,
            'editando' => $editando,
            'errores' => $errores,
        ]);
    }

    /**
     * Lógica de creación de usuario
     */
    private function crearUsuario(): array
    {
        $errores = [];
        $nombre = trim($this->postData('nombre', ''));
        $email = trim($this->postData('email', ''));
        $password = $this->postData('password', '');
        $empresa = trim($this->postData('empresa', ''));
        $telefono = trim($this->postData('telefono', ''));
        $rol = (int) $this->postData('rol', 3);

        if (empty($nombre)) $errores[] = 'El nombre es obligatorio';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'Email inválido';
        if (strlen($password) < 6) $errores[] = 'La contraseña debe tener al menos 6 caracteres';
        if (Usuario::emailExists($email)) $errores[] = 'Este email ya está registrado';
        if (!in_array($rol, [1, 2, 3, 4])) $errores[] = 'Rol inválido';

        if (empty($errores)) {
            $usuario = new Usuario();
            $usuario->setNombre($nombre);
            $usuario->setEmail($email);
            $usuario->setPassword($password);
            $usuario->setEmpresa($empresa ?: null);
            $usuario->setTelefono($telefono ?: null);
            $usuario->setRol($rol);
            if (!$usuario->create()) {
                $errores[] = 'Error al crear el usuario';
            }
        }

        return $errores;
    }

    /**
     * Lógica de actualización de usuario
     */
    private function actualizarUsuario(): array
    {
        $errores = [];
        $id = (int) $this->postData('id', 0);
        $usuario = $id ? Usuario::findById($id) : null;

        if (!$usuario) {
            return ['Usuario no encontrado'];
        }

        $nombre = trim($this->postData('nombre', ''));
        $email = trim($this->postData('email', ''));
        $empresa = trim($this->postData('empresa', ''));
        $telefono = trim($this->postData('telefono', ''));
        $rol = (int) $this->postData('rol', $usuario->getRol());

        if (empty($nombre)) $errores[] = 'El nombre es obligatorio';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'Email inválido';
        if (Usuario::emailExists($email, $id)) $errores[] = 'Este email ya está registrado';
        if (!in_array($rol, [1, 2, 3, 4])) $errores[] = 'Rol inválido';

        if (empty($errores)) {
            $usuario->setNombre($nombre);
            $usuario->setEmail($email);
            $usuario->setEmpresa($empresa ?: null);
            $usuario->setTelefono($telefono ?: null);
            // No permitir cambiar su propio rol
            if ($id !== Auth::id()) {
                $usuario->setRol($rol);
            }
            if (!$usuario->update()) {
                $errores[] = 'Error al actualizar el usuario';
            }
        }

        return $errores;
    }

    /**
     * GET/POST /tipos-consultoria.php - Tipos de consultoría
     */
    public function tiposConsultoria(): void
    {
        AuthMiddleware::requireAdminOrSistemas();

        $mensaje = null;
        $mensajeTipo = null;

        if ($this->isPost()) {
            $action = $this->postData('action', '');

            switch ($action) {
                case 'create':
                    $nombre = trim($this->postData('nombre', ''));
                    $descripcion = trim($this->postData('descripcion', ''));
                    $orden = (int) $this->postData('orden', 0);
                    if (empty($nombre)) {
                        $mensajeTipo = 'danger';
                        $mensaje = 'El nombre es obligatorio';
                    } else {
                        Database::insert('tipos_consultoria', [
                            'nombre' => $nombre,
                            'descripcion' => $descripcion ?: null,
                            'orden' => $orden,
                            'activo' => 1,
                        ]);
                        $mensajeTipo = 'success';
                        $mensaje = 'Tipo creado correctamente';
                    }
                    break;

                case 'update':
                    $id = (int) $this->postData('id', 0);
                    $nombre = trim($this->postData('nombre', ''));
                    $descripcion = trim($this->postData('descripcion', ''));
                    $orden = (int) $this->postData('orden', 0);
                    if ($id && !empty($nombre)) {
                        Database::update('tipos_consultoria', [
                            'nombre' => $nombre,
                            'descripcion' => $descripcion ?: null,
                            'orden' => $orden,
                        ], 'id = ?', [$id]);
                        $mensajeTipo = 'success';
                        $mensaje = 'Tipo actualizado';
                    } else {
                        $mensajeTipo = 'danger';
                        $mensaje = 'El nombre es obligatorio';
                    }
                    break;

                case 'toggle':
                    $id = (int) $this->postData('id', 0);
                    $activo = (int) $this->postData('activo', 0);
                    if ($id) {
                        Database::update('tipos_consultoria', ['activo' => $activo], 'id = ?', [$id]);
                        $mensajeTipo = 'success';
                        $mensaje = $activo ? 'Tipo activado' : 'Tipo desactivado';
                    }
                    break;

                case 'delete':
                    $id = (int) $this->postData('id', 0);
                    if ($id) {
                        // Solo eliminar si no hay tickets asociados
                        $count = Database::fetchOne("SELECT COUNT(*) as c FROM tickets WHERE tipo_consultoria_id = ?", [$id]);
                        if ((int)$count['c'] > 0) {
                            $mensajeTipo = 'danger';
                            $mensaje = 'No se puede eliminar: hay ' . $count['c'] . ' tickets asociados. Desactívalo en su lugar.';
                        } else {
                            Database::delete('tipos_consultoria', 'id = ?', [$id]);
                            $mensajeTipo = 'success';
                            $mensaje = 'Tipo eliminado';
                        }
                    }
                    break;
            }
        }

        $tipos = Database::fetchAll("
            SELECT tc.*, COUNT(t.id) as tickets_count 
            FROM tipos_consultoria tc 
            LEFT JOIN tickets t ON tc.id = t.tipo_consultoria_id 
            GROUP BY tc.id 
            ORDER BY tc.orden ASC, tc.nombre ASC
        ");

        $this->renderWithLayout('admin/tipos-consultoria', [
            'pageTitle' => 'Tipos de Consultoría',
            'tipos' => $tipos,
            'mensaje' => $mensaje,
            'mensajeTipo' => $mensajeTipo,
        ]);
    }

    /**
     * GET /reportes.php - Reportes y estadísticas
     */
    public function reportes(): void
    {
        AuthMiddleware::requireAdminOrSistemas();

        // Datos para reportes
        $porEstado = Database::fetchAll("SELECT estado, COUNT(*) as total FROM tickets GROUP BY estado ORDER BY FIELD(estado, 'nuevo','asignado','en_progreso','pendiente_cliente','resuelto','cerrado')");
        $porPrioridad = Database::fetchAll("SELECT prioridad, COUNT(*) as total FROM tickets GROUP BY prioridad ORDER BY FIELD(prioridad, 'critica','alta','media','baja')");
        $porFuente = Database::fetchAll("SELECT fuente, COUNT(*) as total FROM tickets GROUP BY fuente");
        $porTipo = Database::fetchAll("SELECT tc.nombre, COUNT(*) as total FROM tickets t LEFT JOIN tipos_consultoria tc ON t.tipo_consultoria_id = tc.id GROUP BY tc.nombre ORDER BY total DESC");
        $porConsultor = Database::fetchAll("SELECT COALESCE(u.nombre, 'Sin asignar') as consultor, COUNT(*) as total, SUM(t.tiempo_invertido) as tiempo_total FROM tickets t LEFT JOIN usuarios u ON t.consultor_id = u.id GROUP BY u.nombre ORDER BY total DESC");

        // Tickets por mes
        $anioSeleccionado = (int) ($_GET['anio'] ?? date('Y'));
        $aniosDisponibles = Database::fetchAll("SELECT DISTINCT YEAR(fecha_creacion) as anio FROM tickets ORDER BY anio DESC");

        $porMesRaw = Database::fetchAll("SELECT DATE_FORMAT(fecha_creacion, '%Y-%m') as mes, COUNT(*) as total FROM tickets WHERE YEAR(fecha_creacion) = ? GROUP BY mes ORDER BY mes ASC", [$anioSeleccionado]);
        $porMesMap = [];
        foreach ($porMesRaw as $row) {
            $porMesMap[$row['mes']] = (int) $row['total'];
        }
        $porMes = [];
        $mesesNombres = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        for ($m = 1; $m <= 12; $m++) {
            $mesKey = $anioSeleccionado . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
            $porMes[] = ['mes' => $mesesNombres[$m - 1], 'total' => $porMesMap[$mesKey] ?? 0];
        }

        $topClientes = Database::fetchAll("SELECT u.nombre, u.empresa, COUNT(*) as total FROM tickets t INNER JOIN usuarios u ON t.cliente_id = u.id GROUP BY u.id ORDER BY total DESC LIMIT 5");

        $metricas = Database::fetchOne("SELECT
            COUNT(*) as total,
            COUNT(CASE WHEN estado IN ('nuevo','asignado','en_progreso','pendiente_cliente') THEN 1 END) as abiertos,
            COUNT(CASE WHEN estado IN ('resuelto','cerrado') THEN 1 END) as cerrados,
            COUNT(CASE WHEN estado = 'nuevo' THEN 1 END) as sin_atender,
            COUNT(CASE WHEN prioridad = 'critica' THEN 1 END) as criticos,
            ROUND(AVG(CASE WHEN fecha_resolucion IS NOT NULL THEN TIMESTAMPDIFF(HOUR, fecha_creacion, fecha_resolucion) END), 1) as horas_promedio_resolucion,
            ROUND(SUM(tiempo_invertido), 1) as tiempo_total_invertido,
            COUNT(CASE WHEN fuente = 'email' THEN 1 END) as desde_email,
            COUNT(CASE WHEN fecha_creacion >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as ultima_semana,
            COUNT(CASE WHEN fecha_creacion >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as ultimo_mes
            FROM tickets");

        $tasaResolucion = $metricas['total'] > 0 ? round(($metricas['cerrados'] / $metricas['total']) * 100) : 0;

        $this->renderWithLayout('admin/reportes', [
            'pageTitle' => 'Reportes',
            'porEstado' => $porEstado,
            'porPrioridad' => $porPrioridad,
            'porFuente' => $porFuente,
            'porTipo' => $porTipo,
            'porConsultor' => $porConsultor,
            'porMes' => $porMes,
            'topClientes' => $topClientes,
            'metricas' => $metricas,
            'tasaResolucion' => $tasaResolucion,
            'anioSeleccionado' => $anioSeleccionado,
            'aniosDisponibles' => $aniosDisponibles,
        ]);
    }

    /**
     * GET/POST /api-keys.php - Gestión de API Keys
     */
    public function apiKeys(): void
    {
        AuthMiddleware::requireAdminOrSistemas();

        $mensaje = null;
        $mensajeTipo = null;
        $nuevaKey = null;

        if ($this->isPost()) {
            $action = $this->postData('action', '');

            switch ($action) {
                case 'create':
                    $nombre = trim($this->postData('nombre', ''));
                    if (empty($nombre)) {
                        $mensajeTipo = 'danger';
                        $mensaje = 'El nombre es obligatorio';
                    } else {
                        try {
                            $result = ApiAuth::createApiKey($nombre, ['tickets.create']);
                            $nuevaKey = $result['api_key'];
                            $mensajeTipo = 'success';
                            $mensaje = "API Key creada. ¡Cópiala ahora! No se puede recuperar después.";
                        } catch (\Exception $e) {
                            $mensajeTipo = 'danger';
                            $mensaje = 'Error al crear: ' . $e->getMessage();
                        }
                    }
                    break;

                case 'toggle':
                    $id = (int) $this->postData('id', 0);
                    $activo = (int) $this->postData('activo', 0);
                    try {
                        Database::update('api_keys', ['activo' => $activo], 'id = ?', [$id]);
                        $mensajeTipo = 'success';
                        $mensaje = $activo ? 'API Key activada' : 'API Key desactivada';
                    } catch (\Exception $e) {
                        $mensajeTipo = 'danger';
                        $mensaje = 'Error: ' . $e->getMessage();
                    }
                    break;

                case 'delete':
                    $id = (int) $this->postData('id', 0);
                    try {
                        Database::delete('api_keys', 'id = ?', [$id]);
                        $mensajeTipo = 'success';
                        $mensaje = 'API Key eliminada permanentemente';
                    } catch (\Exception $e) {
                        $mensajeTipo = 'danger';
                        $mensaje = 'Error: ' . $e->getMessage();
                    }
                    break;
            }
        }

        $apiKeys = Database::fetchAll("SELECT * FROM api_keys ORDER BY created_at DESC");

        $stats = Database::fetchOne("SELECT
            COUNT(*) as total_requests,
            COUNT(CASE WHEN response_code = 201 THEN 1 END) as successful,
            COUNT(CASE WHEN response_code >= 400 THEN 1 END) as failed,
            COUNT(CASE WHEN created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as last_24h
            FROM api_logs");

        $allLogs = Database::fetchAll("SELECT l.*, k.nombre as key_nombre
            FROM api_logs l
            LEFT JOIN api_keys k ON l.api_key_id = k.id
            ORDER BY l.created_at DESC LIMIT 200");
        $pagLogs = paginarArray($allLogs, (int)($_GET['page'] ?? 1), (int)($_GET['per_page'] ?? 10));

        $this->renderWithLayout('admin/api-keys', [
            'pageTitle' => 'Gestión de API Keys',
            'apiKeys' => $apiKeys,
            'stats' => $stats,
            'logs' => $pagLogs['items'],
            'pagLogs' => $pagLogs['paginacion'],
            'mensaje' => $mensaje,
            'mensajeTipo' => $mensajeTipo,
            'nuevaKey' => $nuevaKey,
        ]);
    }
}
