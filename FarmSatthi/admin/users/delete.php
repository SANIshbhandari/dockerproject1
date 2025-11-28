<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../auth/session.php';

// Only admin can access
requirePermission('admin');

$conn = getDBConnection();
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    setFlashMessage("Invalid user ID.", 'error');
    redirect('index.php');
}

// Prevent deleting yourself
if ($id == getCurrentUserId()) {
    setFlashMessage("You cannot delete your own account.", 'error');
    redirect('index.php');
}

$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        setFlashMessage("User deleted successfully!", 'success');
    } else {
        setFlashMessage("User not found.", 'error');
    }
} else {
    setFlashMessage("Failed to delete user. Please try again.", 'error');
}

$stmt->close();
redirect('index.php');
?>
