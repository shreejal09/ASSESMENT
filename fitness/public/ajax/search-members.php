<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Start session for CSRF if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only accept AJAX requests
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['error' => 'Direct access not allowed']);
    exit;
}

// Check if user is logged in (optional, depending on your needs)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Get search term
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($search) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

try {
    $search_term = "%$search%";
    
    // Search members by name, email, or phone
    $sql = "
        SELECT 
            m.id,
            CONCAT(m.first_name, ' ', m.last_name) as name,
            m.email,
            m.phone,
            m.status,
            m.join_date,
            (SELECT COUNT(*) FROM attendance WHERE member_id = m.id) as total_visits
        FROM members m
        WHERE 
            m.first_name LIKE ? OR 
            m.last_name LIKE ? OR 
            m.email LIKE ? OR 
            m.phone LIKE ? OR
            CONCAT(m.first_name, ' ', m.last_name) LIKE ?
        ORDER BY 
            CASE 
                WHEN m.first_name LIKE ? THEN 1
                WHEN m.last_name LIKE ? THEN 2
                WHEN m.email LIKE ? THEN 3
                WHEN m.phone LIKE ? THEN 4
                ELSE 5
            END,
            m.first_name, m.last_name
        LIMIT 10
    ";
    
    $stmt = $pdo->prepare($sql);
    
    // Execute with parameters
    $params = array_fill(0, 5, $search_term);
    $params = array_merge($params, array_fill(0, 4, $search_term));
    
    $stmt->execute($params);
    $results = $stmt->fetchAll();
    
    // Format results
    $formatted_results = [];
    foreach ($results as $row) {
        $formatted_results[] = [
            'id' => $row['id'],
            'text' => htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'),
            'name' => htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'),
            'email' => htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'),
            'phone' => htmlspecialchars($row['phone'] ?? 'N/A', ENT_QUOTES, 'UTF-8'),
            'status' => htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'),
            'status_class' => get_status_badge($row['status']),
            'join_date' => format_date($row['join_date']),
            'visits' => $row['total_visits']
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'results' => $formatted_results
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Search failed',
        'message' => $e->getMessage()
    ]);
}
?>