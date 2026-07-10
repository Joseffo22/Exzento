<?php
session_start();
require_once __DIR__ . '/../assets/php/conexiones/conexionMySqli.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /lista-tickets');
    exit;
}

if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['error'] = 'Debes iniciar sesión para eliminar un ticket.';
    header('Location: /login');
    exit;
}

$id_ticket = isset($_POST['id_ticket']) ? (int) $_POST['id_ticket'] : 0;
$id_cliente = (int) $_SESSION['id_usuario'];

if ($id_ticket <= 0) {
    $_SESSION['error'] = 'Ticket no válido.';
    header('Location: /lista-tickets');
    exit;
}

$stmt = $conn->prepare('SELECT id, imagen_ticket, foto_ticket FROM ticket WHERE id = ? AND id_cliente = ?');
$stmt->bind_param('ii', $id_ticket, $id_cliente);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ticket) {
    $_SESSION['error'] = 'No se encontró el ticket o no tienes permiso para eliminarlo.';
    header('Location: /lista-tickets');
    exit;
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare('DELETE FROM facturas WHERE ticket_id = ?');
    $stmt->bind_param('i', $id_ticket);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare('DELETE FROM ticket WHERE id = ? AND id_cliente = ?');
    $stmt->bind_param('ii', $id_ticket, $id_cliente);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('No se pudo eliminar el ticket.');
    }
    $stmt->close();

    $conn->commit();

    $archivos = [
        __DIR__ . '/../../archivos/tickets/' . ($ticket['imagen_ticket'] ?? ''),
        __DIR__ . '/../../archivos/fotos_tickets/' . ($ticket['foto_ticket'] ?? ''),
        __DIR__ . '/../../archivos/qrs/qr_' . $id_ticket . '.png',
    ];

    foreach ($archivos as $ruta) {
        if ($ruta && is_file($ruta)) {
            @unlink($ruta);
        }
    }

    $_SESSION['mensaje'] = "Ticket #{$id_ticket} eliminado correctamente.";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = 'Error al eliminar el ticket: ' . $e->getMessage();
}

$conn->close();

$redirect = $_POST['redirect'] ?? '/lista-tickets';
if (!is_string($redirect) || !preg_match('#^/[a-z0-9\-/?=&%]*$#i', $redirect)) {
    $redirect = '/lista-tickets';
}

header('Location: ' . $redirect);
exit;
