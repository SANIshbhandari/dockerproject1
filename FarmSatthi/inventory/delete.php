<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../auth/session.php';

requirePermission('manager');

$conn = getDBConnection();
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    setFlashMessage("Invalid inventory ID.", 'error');
    redirect('index.php');
}

// Verify record ownership
verifyRecordOwnership($conn, 'inventory', $id, 'index.php');

$isolationWhere = getDataIsolationWhere();
$stmt = $conn->prepare("DELETE FROM inventory WHERE id = ? AND $isolationWhere");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        setFlashMessage("Inventory item deleted successfully!", 'success');
    } else {
        setFlashMessage("Inventory item not found.", 'error');
    }
} else {
    setFlashMessage("Failed to delete inventory item. Please try again.", 'error');
}

$stmt->close();
redirect('index.php');
?>
