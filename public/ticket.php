<?php
require_once __DIR__ . '/../app/bootstrap.php';

$controller = new \App\Controllers\TicketController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    match ($action) {
        'assign' => $controller->assign(),
        'comment' => $controller->comment(),
        'edit_comment' => $controller->editComment(),
        'resolve_with_note' => $controller->resolveWithNote(),
        default => $controller->show(),
    };
} else {
    $controller->show();
}
