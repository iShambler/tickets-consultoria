<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Utils\Auth;
use App\Utils\Database;

Auth::requireAuth();
Auth::requireRole(1); // Solo administradores

// ============================================
// CONSULTAS DE DATOS
// ============================================

// Tickets por estado
$porEstado = Database::fetchAll("SELECT estado, COUNT(*) as total FROM tickets GROUP BY estado ORDER BY FIELD(estado, 'nuevo','asignado','en_progreso','pendiente_cliente','resuelto','cerrado')");

// Tickets por prioridad
$porPrioridad = Database::fetchAll("SELECT prioridad, COUNT(*) as total FROM tickets GROUP BY prioridad ORDER BY FIELD(prioridad, 'critica','alta','media','baja')");

// Tickets por fuente
$porFuente = Database::fetchAll("SELECT fuente, COUNT(*) as total FROM tickets GROUP BY fuente");

// Tickets por tipo consultoría
$porTipo = Database::fetchAll("SELECT tc.nombre, COUNT(*) as total FROM tickets t LEFT JOIN tipos_consultoria tc ON t.tipo_consultoria_id = tc.id GROUP BY tc.nombre ORDER BY total DESC");

// Tickets por consultor
$porConsultor = Database::fetchAll("SELECT COALESCE(u.nombre, 'Sin asignar') as consultor, COUNT(*) as total, SUM(t.tiempo_invertido) as tiempo_total FROM tickets t LEFT JOIN usuarios u ON t.consultor_id = u.id GROUP BY u.nombre ORDER BY total DESC");

// Tickets por mes del año seleccionado
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

// Top 5 clientes con más tickets
$topClientes = Database::fetchAll("SELECT u.nombre, u.empresa, COUNT(*) as total FROM tickets t INNER JOIN usuarios u ON t.cliente_id = u.id GROUP BY u.id ORDER BY total DESC LIMIT 5");

// Métricas generales
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

// Tasa de resolución
$tasaResolucion = $metricas['total'] > 0 ? round(($metricas['cerrados'] / $metricas['total']) * 100) : 0;

// Preparar datos para gráficos (JSON para Chart.js)
$chartEstadoLabels = array_column($porEstado, 'estado');
$chartEstadoData = array_column($porEstado, 'total');

$chartPrioridadLabels = array_column($porPrioridad, 'prioridad');
$chartPrioridadData = array_column($porPrioridad, 'total');

$chartFuenteLabels = array_column($porFuente, 'fuente');
$chartFuenteData = array_column($porFuente, 'total');

$chartMesLabels = array_column($porMes, 'mes');
$chartMesData = array_column($porMes, 'total');

$pageTitle = 'Reportes';
include __DIR__ . '/../app/views/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../app/views/layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-graph-up"></i> Reportes</h1>
            </div>
            
            <!-- Métricas principales -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="card text-center border-primary">
                        <div class="card-body py-3">
                            <p class="display-6 mb-0 text-primary"><?= $metricas['total'] ?></p>
                            <small class="text-muted">Total tickets</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center border-warning">
                        <div class="card-body py-3">
                            <p class="display-6 mb-0 text-warning"><?= $metricas['abiertos'] ?></p>
                            <small class="text-muted">Abiertos</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center border-success">
                        <div class="card-body py-3">
                            <p class="display-6 mb-0 text-success"><?= $tasaResolucion ?>%</p>
                            <small class="text-muted">Tasa resolución</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center border-info">
                        <div class="card-body py-3">
                            <p class="display-6 mb-0 text-info"><?= $metricas['horas_promedio_resolucion'] ?? '—' ?></p>
                            <small class="text-muted">Horas prom. resolución</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center border-danger">
                        <div class="card-body py-3">
                            <p class="display-6 mb-0 text-danger"><?= $metricas['sin_atender'] ?></p>
                            <small class="text-muted">Sin atender</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body py-3">
                            <p class="display-6 mb-0"><?= $metricas['ultima_semana'] ?></p>
                            <small class="text-muted">Última semana</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos fila 1 -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header"><h6 class="mb-0">Tickets por estado</h6></div>
                        <div class="card-body">
                            <canvas id="chartEstado" height="250"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header"><h6 class="mb-0">Tickets por prioridad</h6></div>
                        <div class="card-body">
                            <canvas id="chartPrioridad" height="250"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header"><h6 class="mb-0">Tickets por fuente</h6></div>
                        <div class="card-body">
                            <canvas id="chartFuente" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráfico temporal -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Tickets por mes — <?= $anioSeleccionado ?></h6>
                            <div>
                                <?php foreach ($aniosDisponibles as $a): ?>
                                    <a href="?anio=<?= $a['anio'] ?>" class="btn btn-sm <?= $a['anio'] == $anioSeleccionado ? 'btn-primary' : 'btn-outline-secondary' ?>">
                                        <?= $a['anio'] ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="chartMes" height="50"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tablas detalle -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header"><h6 class="mb-0"><i class="bi bi-person-badge"></i> Por consultor</h6></div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr><th>Consultor</th><th class="text-center">Tickets</th><th class="text-center">Horas</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($porConsultor as $row): ?>
                                    <tr>
                                        <td><?= e($row['consultor']) ?></td>
                                        <td class="text-center"><span class="badge bg-primary"><?= $row['total'] ?></span></td>
                                        <td class="text-center"><small><?= formatTime((float)($row['tiempo_total'] ?? 0)) ?></small></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header"><h6 class="mb-0"><i class="bi bi-people"></i> Top 5 clientes</h6></div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr><th>Cliente</th><th>Empresa</th><th class="text-center">Tickets</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topClientes as $row): ?>
                                    <tr>
                                        <td><?= e($row['nombre']) ?></td>
                                        <td><small class="text-muted"><?= e($row['empresa'] ?? '—') ?></small></td>
                                        <td class="text-center"><span class="badge bg-primary"><?= $row['total'] ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Por tipo consultoría -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header"><h6 class="mb-0"><i class="bi bi-tags"></i> Por tipo de consultoría</h6></div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr><th>Tipo</th><th class="text-center">Tickets</th><th>Proporción</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($porTipo as $row): 
                                        $pct = $metricas['total'] > 0 ? round(($row['total'] / $metricas['total']) * 100) : 0;
                                    ?>
                                    <tr>
                                        <td><?= e($row['nombre'] ?? 'Sin tipo') ?></td>
                                        <td class="text-center"><span class="badge bg-info"><?= $row['total'] ?></span></td>
                                        <td>
                                            <div class="progress" style="height: 18px;">
                                                <div class="progress-bar bg-info" style="width: <?= $pct ?>%"><?= $pct ?>%</div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header"><h6 class="mb-0"><i class="bi bi-lightning"></i> Resumen rápido</h6></div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">📊 <strong><?= $metricas['total'] ?></strong> tickets en total</li>
                                <li class="mb-2">📬 <strong><?= $metricas['desde_email'] ?></strong> creados automáticamente desde email</li>
                                <li class="mb-2">⏱️ <strong><?= $metricas['tiempo_total_invertido'] ?? 0 ?></strong> horas invertidas en total</li>
                                <li class="mb-2">📅 <strong><?= $metricas['ultimo_mes'] ?></strong> tickets en el último mes</li>
                                <li class="mb-2">🔴 <strong><?= $metricas['criticos'] ?></strong> tickets con prioridad crítica</li>
                                <li class="mb-0">✅ Tasa de resolución: <strong><?= $tasaResolucion ?>%</strong></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Colores
const estadoColors = {
    'nuevo': '#0dcaf0', 'asignado': '#0d6efd', 'en_progreso': '#ffc107',
    'pendiente_cliente': '#6c757d', 'resuelto': '#198754', 'cerrado': '#212529'
};
const prioridadColors = { 'critica': '#dc3545', 'alta': '#ffc107', 'media': '#0d6efd', 'baja': '#6c757d' };
const fuenteColors = { 'manual': '#6c757d', 'email': '#0d6efd', 'api': '#198754' };

const estadoNames = {
    'nuevo': 'Nuevo', 'asignado': 'Asignado', 'en_progreso': 'En progreso',
    'pendiente_cliente': 'Pend. cliente', 'resuelto': 'Resuelto', 'cerrado': 'Cerrado'
};
const prioridadNames = { 'critica': 'Crítica', 'alta': 'Alta', 'media': 'Media', 'baja': 'Baja' };
const fuenteNames = { 'manual': 'Manual', 'email': 'Email', 'api': 'API' };

// Gráfico Estado (Doughnut)
new Chart(document.getElementById('chartEstado'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($chartEstadoLabels) ?>.map(l => estadoNames[l] || l),
        datasets: [{
            data: <?= json_encode($chartEstadoData) ?>,
            backgroundColor: <?= json_encode($chartEstadoLabels) ?>.map(l => estadoColors[l] || '#999')
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
});

// Gráfico Prioridad (Doughnut)
new Chart(document.getElementById('chartPrioridad'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($chartPrioridadLabels) ?>.map(l => prioridadNames[l] || l),
        datasets: [{
            data: <?= json_encode($chartPrioridadData) ?>,
            backgroundColor: <?= json_encode($chartPrioridadLabels) ?>.map(l => prioridadColors[l] || '#999')
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
});

// Gráfico Fuente (Doughnut)
new Chart(document.getElementById('chartFuente'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($chartFuenteLabels) ?>.map(l => fuenteNames[l] || l),
        datasets: [{
            data: <?= json_encode($chartFuenteData) ?>,
            backgroundColor: <?= json_encode($chartFuenteLabels) ?>.map(l => fuenteColors[l] || '#999')
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
});

// Gráfico Mes (Bar)
new Chart(document.getElementById('chartMes'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartMesLabels) ?>,
        datasets: [{
            label: 'Tickets creados',
            data: <?= json_encode($chartMesData) ?>,
            backgroundColor: '#0d6efd',
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
</script>

<?php include __DIR__ . '/../app/views/layouts/footer.php'; ?>
