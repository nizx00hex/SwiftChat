<?php
/**
 * SwiftChat Requests API
 * Endpoints for chat requests
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../server/php/request_manager.php';
require_once __DIR__ . '/../../server/php/auth.php';
require_once __DIR__ . '/../../security/csrf_protection.php';

// Authenticate
$auth = new AuthSystem();
$currentUser = $auth->getCurrentUser();

if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['error' => 'Please login first.']);
    exit;
}

// CSRF check for state-changing requests
if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
    $csrf = new CSRFProtection();
    if (!$csrf->validateToken()) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid security token.']);
        exit;
    }
}

$requestManager = new RequestManager();
$action = $_GET['action'] ?? '';
$userId = $currentUser['id'];

switch ($action) {
    case 'send':
        // Send a chat request
        $input = json_decode(file_get_contents('php://input'), true);
        $username = $input['username'] ?? '';
        $message = $input['message'] ?? '';
        
        $result = $requestManager->sendRequest($userId, $username, $message);
        echo json_encode($result);
        break;
        
    case 'accept':
        // Accept a chat request
        $input = json_decode(file_get_contents('php://input'), true);
        $requestId = (int)($input['request_id'] ?? 0);
        
        $result = $requestManager->acceptRequest($userId, $requestId);
        echo json_encode($result);
        break;
        
    case 'reject':
        // Reject a chat request
        $input = json_decode(file_get_contents('php://input'), true);
        $requestId = (int)($input['request_id'] ?? 0);
        
        $result = $requestManager->rejectRequest($userId, $requestId);
        echo json_encode($result);
        break;
        
    case 'block':
        // Block a user
        $input = json_decode(file_get_contents('php://input'), true);
        $targetUserId = (int)($input['user_id'] ?? 0);
        $reason = $input['reason'] ?? '';
        
        $result = $requestManager->blockUser($userId, $targetUserId, $reason);
        echo json_encode($result);
        break;
        
    case 'unblock':
        // Unblock a user
        $input = json_decode(file_get_contents('php://input'), true);
        $targetUserId = (int)($input['user_id'] ?? 0);
        
        $result = $requestManager->unblockUser($userId, $targetUserId);
        echo json_encode($result);
        break;
        
    case 'pending':
        // Get pending requests
        $result = $requestManager->getPendingRequests($userId);
        echo json_encode($result);
        break;
        
    case 'sent':
        // Get sent requests
        $result = $requestManager->getSentRequests($userId);
        echo json_encode($result);
        break;
        
    case 'blocked':
        // Get blocked users
        $result = $requestManager->getBlockedUsers($userId);
        echo json_encode($result);
        break;
        
    case 'check':
        // Check if can message user
        $targetUserId = (int)($_GET['user_id'] ?? 0);
        $result = $requestManager->canMessage($userId, $targetUserId);
        echo json_encode($result);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action.']);
}
?>