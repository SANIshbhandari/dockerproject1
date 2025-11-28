<?php
require_once __DIR__ . '/../includes/header.php';

requirePermission('manager');

$conn = getDBConnection();
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    setFlashMessage("Invalid livestock ID.", 'error');
    redirect('index.php');
}

// Verify ownership
verifyRecordOwnership($conn, 'livestock', $id, 'index.php');

// Get animal info for logging
$isolationWhere = getDataIsolationWhere();
$stmt = $conn->prepare("SELECT animal_tag FROM livestock WHERE id = ? AND $isolationWhere");
$stmt->bind_param("i", $id);
$stmt->execute();
$animal = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$animal) {
    setFlashMessage("Livestock not found.", 'error');
    redirect('index.php');
}

// Delete
$stmt = $conn->prepare("DELETE FROM livestock WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $stmt->close();
    logActivity('delete', 'livestock', "Deleted livestock {$animal['animal_tag']}");
    setFlashMessage("Livestock deleted successfully.", 'success');
} else {
    $stmt->close();
    setFlashMessage("Failed to delete livestock.", 'error');
}

redirect('index.php');
?>
