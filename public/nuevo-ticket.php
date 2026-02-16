<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Models\Ticket;
use App\Utils\Auth;
use App\Utils\Validator;
use App\Utils\Database;

Auth::requireAuth();

$error = '';
$success = false;

// Obtener tipos de consultoría
$sql = "SELECT * FROM tipos_consultoria WHERE activo = 1 ORDER BY orden ASC, nombre ASC";
$tipos = Database::fetchAll($sql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'] ?? '';
    $tipo_consultoria_id = $_POST['tipo_consultoria_id'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $prioridad = $_POST['prioridad'] ?? 'media';
    
    $validator = new Validator([
        'titulo' => $titulo,
        'descripcion' => $descripcion,
        'tipo_consultoria_id' => $tipo_consultoria_id
    ]);
    
    $validator->required('titulo')->minLength('titulo', 5)
              ->required('descripcion')->minLength('descripcion', 20)
              ->required('tipo_consultoria_id');
    
    if ($validator->passes()) {
        try {
            $ticket = new Ticket();
            $ticket->setClienteId(Auth::id());
            $ticket->setTitulo($titulo);
            $ticket->setDescripcion($descripcion);
            $ticket->setTipoConsultoriaId((int)$tipo_consultoria_id);
            $ticket->setPrioridad($prioridad);
            
            if ($ticket->create()) {
                setFlash('success', 'Ticket creado exitosamente con número: ' . $ticket->getNumero());
                redirect('dashboard.php');
            } else {
                $error = 'Error al crear el ticket. Intenta nuevamente.';
            }
        } catch (Exception $e) {
            $error = 'Error al crear el ticket: ' . $e->getMessage();
        }
    } else {
        $error = 'Por favor, completa todos los campos correctamente.';
    }
}

$pageTitle = 'Nuevo Ticket';
include __DIR__ . '/../app/views/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../app/views/layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Crear Nuevo Ticket</h1>
                <a href="<?= base_url('dashboard.php') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> <?= e($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Información del Ticket</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="titulo" class="form-label">
                                        Título del ticket <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="titulo" name="titulo" 
                                           value="<?= e($_POST['titulo'] ?? '') ?>" 
                                           placeholder="Breve descripción del problema o consulta"
                                           required maxlength="200">
                                    <small class="text-muted">Mínimo 5 caracteres</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="tipo_consultoria_id" class="form-label">
                                        Tipo de consultoría <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="tipo_consultoria_id" name="tipo_consultoria_id" required>
                                        <option value="">Selecciona un tipo</option>
                                        <?php foreach ($tipos as $tipo): ?>
                                            <option value="<?= $tipo['id'] ?>" 
                                                    <?= ($_POST['tipo_consultoria_id'] ?? '') == $tipo['id'] ? 'selected' : '' ?>>
                                                <?= e($tipo['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="prioridad" class="form-label">Prioridad</label>
                                    <select class="form-select" id="prioridad" name="prioridad">
                                        <?php foreach (config('prioridades') as $key => $value): ?>
                                            <option value="<?= e($key) ?>" 
                                                    <?= ($_POST['prioridad'] ?? 'media') === $key ? 'selected' : '' ?>>
                                                <?= e($value) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Indica la urgencia de tu solicitud</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="descripcion" class="form-label">
                                        Descripción detallada <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" 
                                              rows="8" required 
                                              placeholder="Describe detalladamente tu problema o consulta..."><?= e($_POST['descripcion'] ?? '') ?></textarea>
                                    <small class="text-muted">Mínimo 20 caracteres. Proporciona todos los detalles relevantes.</small>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-plus-circle"></i> Crear Ticket
                                    </button>
                                    <a href="<?= base_url('dashboard.php') ?>" class="btn btn-outline-secondary">
                                        Cancelar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-info-circle"></i> Información</h5>
                            <p class="card-text">
                                Al crear un ticket, nuestro equipo será notificado y un consultor 
                                será asignado para atender tu solicitud.
                            </p>
                            <hr>
                            <h6>Tiempos de respuesta estimados:</h6>
                            <ul class="small">
                                <li><strong>Crítica:</strong> 2-4 horas</li>
                                <li><strong>Alta:</strong> 4-8 horas</li>
                                <li><strong>Media:</strong> 1-2 días</li>
                                <li><strong>Baja:</strong> 3-5 días</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../app/views/layouts/footer.php'; ?>
