<?php
/**
 * Gestión de API Keys (Solo Administradores)
 */
require_once __DIR__ . '/../app/bootstrap.php';

use App\Utils\Auth;
use App\Utils\Database;
use App\Utils\ApiAuth;

// Verificar que es admin
if (!Auth::check() || !Auth::user()->isAdmin()) {
    setFlash('danger', 'No tienes permiso para acceder a esta sección');
    redirect('dashboard.php');
}

$mensaje = null;
$mensajeTipo = null;
$nuevaKey = null;

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $nombre = trim($_POST['nombre'] ?? '');
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
            $id = (int) ($_POST['id'] ?? 0);
            $activo = (int) ($_POST['activo'] ?? 0);
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
            $id = (int) ($_POST['id'] ?? 0);
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

// Obtener todas las API Keys
$apiKeys = Database::fetchAll("SELECT * FROM api_keys ORDER BY created_at DESC");

// Obtener estadísticas de uso
$stats = Database::fetchOne("SELECT 
    COUNT(*) as total_requests,
    COUNT(CASE WHEN response_code = 201 THEN 1 END) as successful,
    COUNT(CASE WHEN response_code >= 400 THEN 1 END) as failed,
    COUNT(CASE WHEN created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as last_24h
    FROM api_logs");

$pageTitle = 'Gestión de API Keys';
include __DIR__ . '/../app/views/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../app/views/layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">🔑 Gestión de API Keys</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">
                    <i class="bi bi-plus-circle"></i> Nueva API Key
                </button>
    </div>

    <?php if ($mensaje): ?>
    <div class="alert alert-<?= $mensajeTipo ?> alert-dismissible fade show">
        <?= e($mensaje) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($nuevaKey): ?>
    <div class="alert alert-warning">
        <h5>⚠️ Copia esta API Key ahora — No se mostrará de nuevo:</h5>
        <div class="input-group mt-2">
            <input type="text" class="form-control font-monospace" value="<?= e($nuevaKey) ?>" id="nuevaKeyInput" readonly>
            <button class="btn btn-outline-secondary" onclick="copiarKey()">
                <i class="bi bi-clipboard"></i> Copiar
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-muted">Total Peticiones</h5>
                    <p class="display-6"><?= $stats['total_requests'] ?? 0 ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-success">Exitosas</h5>
                    <p class="display-6 text-success"><?= $stats['successful'] ?? 0 ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-danger">Fallidas</h5>
                    <p class="display-6 text-danger"><?= $stats['failed'] ?? 0 ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-primary">Últimas 24h</h5>
                    <p class="display-6 text-primary"><?= $stats['last_24h'] ?? 0 ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de API Keys -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">API Keys registradas</h5>
        </div>
        <div class="card-body">
            <?php if (empty($apiKeys)): ?>
                <p class="text-muted text-center py-4">No hay API Keys creadas. Crea una para empezar.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Estado</th>
                            <th>Permisos</th>
                            <th>Creada</th>
                            <th>Último uso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($apiKeys as $key): ?>
                        <tr>
                            <td><?= $key['id'] ?></td>
                            <td><strong><?= e($key['nombre']) ?></strong></td>
                            <td>
                                <?php if ($key['activo']): ?>
                                    <span class="badge bg-success">Activa</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactiva</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $permisos = json_decode($key['permisos'] ?? '[]', true);
                                foreach ($permisos as $perm): ?>
                                    <span class="badge bg-info"><?= e($perm) ?></span>
                                <?php endforeach; ?>
                            </td>
                            <td><?= formatDate($key['created_at']) ?></td>
                            <td><?= $key['last_used_at'] ? formatDate($key['last_used_at']) : '<span class="text-muted">Nunca</span>' ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= $key['id'] ?>">
                                    <input type="hidden" name="activo" value="<?= $key['activo'] ? 0 : 1 ?>">
                                    <button type="submit" class="btn btn-sm btn-<?= $key['activo'] ? 'warning' : 'success' ?>">
                                        <?= $key['activo'] ? 'Desactivar' : 'Activar' ?>
                                    </button>
                                </form>
                                <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar esta API Key permanentemente?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $key['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Últimos logs -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">📊 Últimas peticiones API</h5>
        </div>
        <div class="card-body">
            <?php
            $logs = Database::fetchAll("SELECT l.*, k.nombre as key_nombre 
                FROM api_logs l 
                LEFT JOIN api_keys k ON l.api_key_id = k.id 
                ORDER BY l.created_at DESC LIMIT 20");
            ?>
            <?php if (empty($logs)): ?>
                <p class="text-muted text-center py-4">No hay peticiones registradas aún.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Endpoint</th>
                            <th>Key</th>
                            <th>IP</th>
                            <th>Código</th>
                            <th>Tiempo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= formatDate($log['created_at']) ?></td>
                            <td><code><?= e($log['endpoint']) ?></code></td>
                            <td><?= e($log['key_nombre'] ?? 'N/A') ?></td>
                            <td><code><?= e($log['ip_address']) ?></code></td>
                            <td>
                                <span class="badge bg-<?= $log['response_code'] < 400 ? 'success' : 'danger' ?>">
                                    <?= $log['response_code'] ?>
                                </span>
                            </td>
                            <td><?= number_format($log['response_time'] * 1000, 0) ?>ms</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Crear -->
<div class="modal fade" id="modalCrear" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title">🔑 Nueva API Key</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre identificativo</label>
                        <input type="text" name="nombre" class="form-control" placeholder="ej: n8n-produccion" required>
                        <small class="text-muted">Un nombre para identificar dónde se usa esta key</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Generar API Key</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function copiarKey() {
    const input = document.getElementById('nuevaKeyInput');
    input.select();
    navigator.clipboard.writeText(input.value).then(() => {
        const btn = input.nextElementSibling;
        btn.innerHTML = '<i class="bi bi-check"></i> ¡Copiada!';
        btn.classList.replace('btn-outline-secondary', 'btn-success');
        setTimeout(() => {
            btn.innerHTML = '<i class="bi bi-clipboard"></i> Copiar';
            btn.classList.replace('btn-success', 'btn-outline-secondary');
        }, 3000);
    });
}
</script>

        </main>
    </div>
</div>

<?php include __DIR__ . '/../app/views/layouts/footer.php'; ?>
