<?php
/**
 * SwiftChat Messages API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../server/php/chat_manager.php';
require_once __DIR__ . '/../../server/php/auth.php';
require_once __DIR__ . '/../../security/csrf_protection.php';

$auth = new AuthSystem();
$currentUser = $auth->getCurrentUser();

if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['error' => 'Please login first.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = new CSRFProtection();
    if (!$csrf->validateToken()) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid security token.']);
        exit;
    }
}

$chatManager = new ChatManager();
$action = $_GET['action'] ?? '';
$userId = $currentUser['id'];

switch ($action) {
    case 'send':
        $input = json_decode(file_get_contents('php://input'), true);
        $toUsername = $input['username'] ?? '';
        $message = $input['message'] ?? '';
        
        $result = $chatManager->sendMessage($userId, $toUsername, $message);
        echo json_encode($result);
        break;
        
    case 'history':
        $contactId = (int)($_GET['user_id'] ?? 0);
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);
        
        $result = $chatManager->getConversation($userId, $contactId, $limit, $offset);
        echo json_encode($result);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action.']);
}
?>