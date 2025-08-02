<?php
require_once '../config.php';

// Check if user is admin
if (getUserRole() !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit();
}

// Get dispute ID from query parameter
$dispute_id = isset($_GET['dispute_id']) ? (int)$_GET['dispute_id'] : 0;

if (!$dispute_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid dispute ID']);
    exit();
}

try {
    // Get all responses for this dispute with user information
    $stmt = $pdo->prepare("
        SELECT dr.*, u.full_name as user_name, u.role as user_role
        FROM dispute_responses dr
        LEFT JOIN users u ON dr.user_id = u.id
        WHERE dr.dispute_id = ?
        ORDER BY dr.created_at ASC
    ");
    $stmt->execute([$dispute_id]);
    $responses = $stmt->fetchAll();
    
    // Set content type to JSON
    header('Content-Type: application/json');
    echo json_encode($responses);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
