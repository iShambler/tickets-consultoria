<?php
/**
 * Helper de paginación reutilizable
 * 
 * Uso en controlador:
 *   $paginacion = paginar($totalItems, $paginaActual, $porPagina);
 *   $items = array_slice($todosItems, $paginacion['offset'], $paginacion['por_pagina']);
 * 
 * Uso en vista:
 *   renderPaginacion($paginacion, 'tickets.php', $_GET);
 */

/**
 * Calcula datos de paginación
 */
function paginar(int $total, int $paginaActual = 1, int $porPagina = 15): array
{
    $paginaActual = max(1, $paginaActual);
    $totalPaginas = max(1, (int) ceil($total / $porPagina));
    $paginaActual = min($paginaActual, $totalPaginas);
    $offset = ($paginaActual - 1) * $porPagina;

    return [
        'total' => $total,
        'por_pagina' => $porPagina,
        'pagina_actual' => $paginaActual,
        'total_paginas' => $totalPaginas,
        'offset' => $offset,
        'tiene_anterior' => $paginaActual > 1,
        'tiene_siguiente' => $paginaActual < $totalPaginas,
    ];
}

/**
 * Pagina un array en memoria y devuelve items + datos de paginación
 */
function paginarArray(array $items, int $paginaActual = 1, int $porPagina = 15): array
{
    $paginacion = paginar(count($items), $paginaActual, $porPagina);
    $itemsPagina = array_slice($items, $paginacion['offset'], $paginacion['por_pagina']);

    return [
        'items' => $itemsPagina,
        'paginacion' => $paginacion,
    ];
}

/**
 * Renderiza controles de paginación Bootstrap 5
 */
function renderPaginacion(array $pag, string $baseUrl, array $params = [], string $paramName = 'page', string $perPageParam = ''): void
{
    if ($pag['total'] <= 5) return;

    // Limpiar params vacíos y el param de paginación actual
    unset($params[$paramName]);
    foreach ($params as $k => $v) {
        if ($v === '' || $v === null) unset($params[$k]);
    }

    $buildUrl = function(int $page) use ($baseUrl, $params, $paramName) {
        $params[$paramName] = $page;
        $query = http_build_query($params);
        return base_url($baseUrl) . ($query ? '?' . $query : '');
    };

    $actual = $pag['pagina_actual'];
    $total = $pag['total_paginas'];
    $rango = 2;
    $inicio = max(1, $actual - $rango);
    $fin = min($total, $actual + $rango);

    // Selector de elementos por página
    $ppParam = $perPageParam ?: 'per_' . $paramName;

    echo '<nav aria-label="Paginación" class="mt-3">';
    echo '<div class="d-flex justify-content-between align-items-center">';
    echo '<div class="d-flex align-items-center gap-2">';
    echo '<small class="text-muted">Mostrando ' . min($pag['por_pagina'], $pag['total'] - $pag['offset']) . ' de ' . $pag['total'] . '</small>';
    echo '<select class="form-select form-select-sm" style="width:auto;" onchange="window.location.href=this.value">';
    foreach ([5, 10, 15, 25, 50] as $opt) {
        $p = $params;
        $p[$ppParam] = $opt;
        $p[$paramName] = 1;
        $url = base_url($baseUrl) . '?' . http_build_query($p);
        $sel = ($pag['por_pagina'] === $opt) ? ' selected' : '';
        echo '<option value="' . $url . '"' . $sel . '>' . $opt . '/pág</option>';
    }
    echo '</select>';
    echo '</div>';  // cierre d-flex inner

    if ($pag['total_paginas'] > 1) {
    echo '<ul class="pagination pagination-sm mb-0">';

    // Anterior
    if ($pag['tiene_anterior']) {
        echo '<li class="page-item"><a class="page-link" href="' . $buildUrl($actual - 1) . '">&laquo;</a></li>';
    } else {
        echo '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
    }

    // Primera + elipsis
    if ($inicio > 1) {
        echo '<li class="page-item"><a class="page-link" href="' . $buildUrl(1) . '">1</a></li>';
        if ($inicio > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
    }

    // Rango
    for ($i = $inicio; $i <= $fin; $i++) {
        if ($i === $actual) {
            echo '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            echo '<li class="page-item"><a class="page-link" href="' . $buildUrl($i) . '">' . $i . '</a></li>';
        }
    }

    // Última + elipsis
    if ($fin < $total) {
        if ($fin < $total - 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
        echo '<li class="page-item"><a class="page-link" href="' . $buildUrl($total) . '">' . $total . '</a></li>';
    }

    // Siguiente
    if ($pag['tiene_siguiente']) {
        echo '<li class="page-item"><a class="page-link" href="' . $buildUrl($actual + 1) . '">&raquo;</a></li>';
    } else {
        echo '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
    }

    echo '</ul>';
    } // fin if total_paginas > 1

    echo '</div></nav>';
}
