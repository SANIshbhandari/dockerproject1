<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../auth/session.php';

requirePermission('manager');

$conn = getDBConnection();
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    setFlashMessage("Invalid equipment ID.", 'error');
    redirect('index.php');
}

// Verify record ownership
verifyRecordOwnership($conn, 'equipment', $id, 'index.php');

$isolationWhere = getDataIsolationWhere();
$stmt = $conn->prepare("DELETE FROM equipment WHERE id = ? AND $isolationWhere");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        setFlashMessage("Equipment deleted successfully!", 'success');
    } else {
        setFlashMessage("Equipment not found.", 'error');
    }
} else {
    setFlashMessage("Failed to delete equipment. Please try again.", 'error');
}

$stmt->close();
redirect('index.php');
?>
