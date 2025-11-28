<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../auth/session.php';

requirePermission('manager');

$conn = getDBConnection();
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    setFlashMessage("Invalid crop ID.", 'error');
    redirect('index.php');
}

// Verify record ownership before allowing delete
verifyRecordOwnership($conn, 'crops', $id, 'index.php');

// Delete the crop
$isolationWhere = getDataIsolationWhere();
$stmt = $conn->prepare("DELETE FROM crops WHERE id = ? AND $isolationWhere");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        setFlashMessage("Crop deleted successfully!", 'success');
    } else {
        setFlashMessage("Crop not found.", 'error');
    }
} else {
    setFlashMessage("Failed to delete crop. Please try again.", 'error');
}

$stmt->close();
redirect('index.php');
?>
