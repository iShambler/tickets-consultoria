<?php
/**
 * Controlador de Tickets
 */

namespace App\Controllers;

use App\Models\Ticket;
use App\Utils\Auth;
use App\Utils\Database;
use App\Utils\Validator;
use App\Middleware\AuthMiddleware;

class TicketController extends BaseController
{
    /**
     * GET /ticket.php?id=N - Muestra detalle de un ticket
     */
    public function show(): void
    {
        AuthMiddleware::handle();

        $ticketId = $this->queryData('id');

        if (!$ticketId) {
            setFlash('danger', 'ID de ticket no especificado');
            redirect('dashboard.php');
        }

        $ticket = Ticket::findById((int) $ticketId);

        if (!$ticket) {
            setFlash('danger', 'Ticket no encontrado');
            redirect('dashboard.php');
        }

        // Verificar permisos: cliente solo ve sus tickets
        if (Auth::isCliente() && $ticket->getClienteId() !== Auth::id()) {
            setFlash('danger', 'No tienes permiso para ver este ticket');
            redirect('dashboard.php');
        }

        // Obtener usuarios asignables para el dropdown (solo staff)
        $asignables = [];
        if (Auth::isStaff()) {
            $asignables = \App\Models\Usuario::getAsignables();
        }

        // Obtener comentarios (internos solo para staff)
        $comentarios = $ticket->getComentarios(Auth::isStaff());

        $this->renderWithLayout('tickets/show', [
            'pageTitle' => 'Ticket #' . $ticket->getNumero(),
            'ticket' => $ticket,
            'asignables' => $asignables,
            'comentarios' => $comentarios,
        ]);
    }

    /**
     * GET /tickets.php - Lista todos los tickets (staff)
     */
    public function index(): void
    {
        AuthMiddleware::requireStaff();

        $filtros = [];
        foreach (['prioridad', 'busqueda'] as $filtro) {
            if (!empty($_GET[$filtro])) {
                $filtros[$filtro] = $_GET[$filtro];
            }
        }
        if (!empty($_GET['consultor'])) {
            $filtros['consultor_id'] = $_GET['consultor'];
        }

        $todosTickets = Ticket::getTickets($filtros);
        $allPendientes = [];
        $allEnProgreso = [];
        $allResueltos = [];

        foreach ($todosTickets as $ticket) {
            if (in_array($ticket->getEstado(), ['resuelto', 'cerrado'])) {
                $allResueltos[] = $ticket;
            } elseif ($ticket->getEstado() === 'en_progreso') {
                $allEnProgreso[] = $ticket;
            } else {
                $allPendientes[] = $ticket;
            }
        }

        $defPag = 5;
        $pagPendientes = paginarArray($allPendientes, (int)($_GET['pp'] ?? 1), (int)($_GET['per_pp'] ?? $defPag));
        $pagEnProgreso = paginarArray($allEnProgreso, (int)($_GET['pe'] ?? 1), (int)($_GET['per_pe'] ?? $defPag));
        $pagResueltos = paginarArray($allResueltos, (int)($_GET['pr'] ?? 1), (int)($_GET['per_pr'] ?? $defPag));

        $this->renderWithLayout('tickets/index', [
            'pageTitle' => 'Todos los Tickets',
            'ticketsPendientes' => $pagPendientes['items'],
            'pagPendientes' => $pagPendientes['paginacion'],
            'ticketsEnProgreso' => $pagEnProgreso['items'],
            'pagEnProgreso' => $pagEnProgreso['paginacion'],
            'ticketsResueltos' => $pagResueltos['items'],
            'pagResueltos' => $pagResueltos['paginacion'],
        ]);
    }

    /**
     * POST /ticket.php?action=comment - Añadir comentario (solo admin/sistemas)
     */
    public function comment(): void
    {
        AuthMiddleware::handle();

        // Solo admin y sistemas pueden comentar
        if (!Auth::isAdmin() && !Auth::isSistemas()) {
            setFlash('danger', 'No tienes permiso para comentar');
            redirect('dashboard.php');
        }

        $ticketId = (int) $this->postData('ticket_id', 0);
        $comentario = trim($this->postData('comentario', ''));

        if (!$ticketId || empty($comentario)) {
            setFlash('danger', 'El comentario no puede estar vacío');
            redirect('ticket.php?id=' . $ticketId);
        }

        $ticket = Ticket::findById($ticketId);
        if (!$ticket) {
            setFlash('danger', 'Ticket no encontrado');
            redirect('dashboard.php');
        }

        // Siempre interno (solo visible para staff)
        $comentarioId = $ticket->addComentarioConId(Auth::id(), $comentario, true);

        if ($comentarioId) {
            // Procesar imágenes adjuntas
            $this->procesarImagenesComentario($ticket, $comentarioId);
            setFlash('success', 'Comentario añadido');
        } else {
            setFlash('danger', 'Error al añadir el comentario');
        }

        redirect('ticket.php?id=' . $ticketId . '#comentarios');
    }

    /**
     * POST /ticket.php?action=edit_comment - Editar comentario existente
     */
    public function editComment(): void
    {
        AuthMiddleware::handle();

        if (!Auth::isAdmin() && !Auth::isSistemas()) {
            setFlash('danger', 'No tienes permiso');
            redirect('dashboard.php');
        }

        $comentarioId = (int) $this->postData('comentario_id', 0);
        $ticketId = (int) $this->postData('ticket_id', 0);
        $textoNuevo = trim($this->postData('comentario', ''));

        if (!$comentarioId || !$ticketId || empty($textoNuevo)) {
            setFlash('danger', 'Datos inválidos');
            redirect('ticket.php?id=' . $ticketId);
        }

        // Verificar que el comentario existe y pertenece al usuario (o es admin)
        $com = Database::fetchOne(
            "SELECT * FROM ticket_comentarios WHERE id = ? AND ticket_id = ?",
            [$comentarioId, $ticketId]
        );

        if (!$com) {
            setFlash('danger', 'Comentario no encontrado');
            redirect('ticket.php?id=' . $ticketId);
        }

        // Solo el autor o un admin puede editar
        if ((int)$com['usuario_id'] !== Auth::id() && !Auth::isAdmin()) {
            setFlash('danger', 'No puedes editar este comentario');
            redirect('ticket.php?id=' . $ticketId);
        }

        Database::update('ticket_comentarios', ['comentario' => $textoNuevo], 'id = ?', [$comentarioId]);

        // Procesar nuevas imágenes si las hay
        $ticket = Ticket::findById($ticketId);
        if ($ticket) {
            $this->procesarImagenesComentario($ticket, $comentarioId);
        }

        setFlash('success', 'Comentario actualizado');
        redirect('ticket.php?id=' . $ticketId . '#comentarios');
    }

    /**
     * Procesa y guarda las imágenes subidas con un comentario
     */
    private function procesarImagenesComentario(Ticket $ticket, int $comentarioId): void
    {
        if (empty($_FILES['imagenes']['name'][0])) {
            return;
        }

        $uploadDir = APP_ROOT . '/public/uploads/tickets/' . $ticket->getId() . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 10 * 1024 * 1024; // 10MB

        foreach ($_FILES['imagenes']['name'] as $i => $name) {
            if ($_FILES['imagenes']['error'][$i] !== UPLOAD_ERR_OK) continue;
            if (!in_array($_FILES['imagenes']['type'][$i], $allowedTypes)) continue;
            if ($_FILES['imagenes']['size'][$i] > $maxSize) continue;

            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $nombreArchivo = 'com-' . $comentarioId . '-' . uniqid() . '.' . $ext;
            $ruta = 'uploads/tickets/' . $ticket->getId() . '/' . $nombreArchivo;
            $rutaCompleta = $uploadDir . $nombreArchivo;

            if (move_uploaded_file($_FILES['imagenes']['tmp_name'][$i], $rutaCompleta)) {
                $data = [
                    'ticket_id' => $ticket->getId(),
                    'usuario_id' => Auth::id(),
                    'comentario_id' => $comentarioId,
                    'nombre_original' => $name,
                    'nombre_archivo' => $nombreArchivo,
                    'ruta' => $ruta,
                    'tipo_mime' => $_FILES['imagenes']['type'][$i],
                    'tamano' => $_FILES['imagenes']['size'][$i],
                ];
                Database::insert('ticket_archivos', $data);
            }
        }
    }

    /**
     * POST /ticket.php?action=resolve_with_note - Resolver ticket con nota de resolución
     */
    public function resolveWithNote(): void
    {
        AuthMiddleware::handle();

        if (!Auth::isAdmin() && !Auth::isSistemas()) {
            setFlash('danger', 'No tienes permiso');
            redirect('dashboard.php');
        }

        $ticketId = (int) $this->postData('ticket_id', 0);
        $comentario = trim($this->postData('comentario', ''));
        $mensajeCliente = trim($this->postData('mensaje_cliente', ''));

        if (!$ticketId || empty($comentario)) {
            setFlash('danger', 'La nota de resolución es obligatoria');
            redirect('ticket.php?id=' . $ticketId);
        }

        $ticket = Ticket::findById($ticketId);
        if (!$ticket) {
            setFlash('danger', 'Ticket no encontrado');
            redirect('dashboard.php');
        }

        // 1. Guardar nota de resolución
        $comentarioId = $ticket->addComentarioConId(Auth::id(), $comentario, true);
        if ($comentarioId) {
            $this->procesarImagenesComentario($ticket, $comentarioId);
        }

        // 2. Marcar como resuelto
        $ticket->setEstado('resuelto');
        $ticket->update(Auth::id());

        // 3. Enviar email al cliente via n8n (no bloqueante)
        $this->notificarResolucion($ticket, $mensajeCliente);

        setFlash('success', 'Ticket resuelto. Nota de resolución guardada.');
        redirect('ticket.php?id=' . $ticketId);
    }

    /**
     * Envía notificación de resolución al cliente vía n8n webhook
     */
    private function notificarResolucion(Ticket $ticket, string $mensajeCliente): void
    {
        try {
            $cliente = Database::fetchOne("SELECT nombre, email FROM usuarios WHERE id = ?", [$ticket->getClienteId()]);
            if (!$cliente || empty($cliente['email'])) return;

            $webhookUrl = 'https://n8n.arelance.com/webhook/ticket-resuelto';
            $payload = json_encode([
                'ticket_id' => $ticket->getId(),
                'ticket_numero' => $ticket->getNumero(),
                'ticket_titulo' => $ticket->getTitulo(),
                'cliente_nombre' => $cliente['nombre'],
                'cliente_email' => $cliente['email'],
                'mensaje' => $mensajeCliente ?: 'Su incidencia ha sido revisada y resuelta por nuestro equipo técnico.',
                'resuelto_por' => Auth::user()->getNombre(),
                'fecha_resolucion' => date('c')
            ]);

            $ch = curl_init($webhookUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Exception $e) {
            error_log('Error notificando resolución: ' . $e->getMessage());
        }
    }

    /**
     * POST /ticket.php?action=assign - Asignar ticket a un usuario de staff
     */
    public function assign(): void
    {
        AuthMiddleware::requireStaff();

        $ticketId = (int) $this->postData('ticket_id', 0);
        $consultorId = (int) $this->postData('consultor_id', 0);
        $autoAsignar = (bool) $this->postData('auto_asignar', false);

        if (!$ticketId) {
            setFlash('danger', 'ID de ticket no especificado');
            redirect('dashboard.php');
        }

        $ticket = Ticket::findById($ticketId);
        if (!$ticket) {
            setFlash('danger', 'Ticket no encontrado');
            redirect('dashboard.php');
        }

        // Auto-asignarse
        if ($autoAsignar) {
            $consultorId = Auth::id();
        }

        // Verificar que el consultor existe y es staff
        if ($consultorId) {
            $consultor = \App\Models\Usuario::findById($consultorId);
            if (!$consultor || $consultor->isCliente()) {
                setFlash('danger', 'El usuario seleccionado no puede ser asignado');
                redirect('ticket.php?id=' . $ticketId);
            }
        }

        // Asignar y cambiar estado
        $ticket->setConsultorId($consultorId ?: null);

        // Si se asigna alguien y el ticket está en 'nuevo' o 'asignado', pasar a 'en_progreso'
        if ($consultorId && in_array($ticket->getEstado(), ['nuevo', 'asignado'])) {
            $ticket->setEstado('en_progreso');
        }

        // Si se desasigna, volver a 'nuevo'
        if (!$consultorId && $ticket->getEstado() === 'en_progreso') {
            $ticket->setEstado('nuevo');
        }

        if ($ticket->update(Auth::id())) {
            $nombre = $consultorId ? ($consultor->getNombre() ?? '') : '';
            $msg = $consultorId
                ? 'Ticket asignado a ' . $nombre . ' y marcado como en progreso'
                : 'Asignación del ticket eliminada';
            setFlash('success', $msg);
        } else {
            setFlash('danger', 'Error al asignar el ticket');
        }

        redirect('ticket.php?id=' . $ticketId);
    }

    /**
     * GET/POST /nuevo-ticket.php - Formulario y creación de ticket
     */
    public function create(): void
    {
        AuthMiddleware::handle();

        $error = '';
        $tipos = Database::fetchAll("SELECT * FROM tipos_consultoria WHERE activo = 1 ORDER BY orden ASC, nombre ASC");

        if ($this->isPost()) {
            $titulo = $this->postData('titulo', '');
            $tipo_consultoria_id = $this->postData('tipo_consultoria_id', '');
            $descripcion = $this->postData('descripcion', '');
            $prioridad = $this->postData('prioridad', 'media');

            $validator = new Validator([
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'tipo_consultoria_id' => $tipo_consultoria_id
            ]);

            $validator->required('titulo')->min('titulo', 5)
                      ->required('descripcion')->min('descripcion', 20)
                      ->required('tipo_consultoria_id');

            if ($validator->passes()) {
                try {
                    $ticket = new Ticket();
                    $ticket->setClienteId(Auth::id());
                    $ticket->setTitulo($titulo);
                    $ticket->setDescripcion($descripcion);
                    $ticket->setTipoConsultoriaId((int) $tipo_consultoria_id);
                    $ticket->setPrioridad($prioridad);

                    if ($ticket->create()) {
                        setFlash('success', 'Ticket creado exitosamente con número: ' . $ticket->getNumero());
                        redirect('dashboard.php');
                    } else {
                        $error = 'Error al crear el ticket. Intenta nuevamente.';
                    }
                } catch (\Exception $e) {
                    $error = 'Error al crear el ticket: ' . $e->getMessage();
                }
            } else {
                $error = 'Por favor, completa todos los campos correctamente.';
            }
        }

        $this->renderWithLayout('tickets/create', [
            'pageTitle' => 'Nuevo Ticket',
            'error' => $error,
            'tipos' => $tipos,
        ]);
    }
}
