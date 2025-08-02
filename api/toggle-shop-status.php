<?php
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if user is a vendor
if (getUserRole() !== 'vendor') {
    http_response_code(403);
    echo json_encode(['error' => 'Only vendors can toggle shop status']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Get current vendor status
    $stmt = $pdo->prepare("SELECT id, is_open FROM vendors WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $vendor = $stmt->fetch();
    
    if (!$vendor) {
        http_response_code(404);
        echo json_encode(['error' => 'Vendor profile not found']);
        exit;
    }
    
    // Toggle status
    $new_status = !$vendor['is_open'];
    
    $stmt = $pdo->prepare("UPDATE vendors SET is_open = ? WHERE id = ?");
    $stmt->execute([$new_status, $vendor['id']]);
    
    echo json_encode([
        'success' => true,
        'is_open' => $new_status,
        'message' => $new_status ? 'Shop is now open' : 'Shop is now closed'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>
