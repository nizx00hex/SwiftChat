<?php
/**
 * SwiftChat Notifications API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../server/php/notification_service.php';
require_once __DIR__ . '/../../server/php/auth.php';

$auth = new AuthSystem();
$currentUser = $auth->getCurrentUser();

if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['error' => 'Please login first.']);
    exit;
}

$notificationService = new NotificationService();
$action = $_GET['action'] ?? '';
$userId = $currentUser['id'];

switch ($action) {
    case 'unread':
        $result = $notificationService->getUnreadNotifications($userId);
        echo json_encode($result);
        break;
        
    case 'count':
        $result = $notificationService->getUnreadCount($userId);
        echo json_encode($result);
        break;
        
    case 'read':
        $notificationId = (int)($_GET['id'] ?? 0);
        $result = $notificationService->markAsRead($notificationId, $userId);
        echo json_encode($result);
        break;
        
    case 'read-all':
        $result = $notificationService->markAllAsRead($userId);
        echo json_encode($result);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action.']);
}
?>