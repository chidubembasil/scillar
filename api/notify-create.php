<?php
    require "configs.php";
    session_start();
    
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    $data = json_decode(file_get_contents('php://input'), true);

    $userId = $data['user_id'] ?? '';
    $propertyId = $data['property_id'] ?? '';
    $action = $data['action'] ?? '';
    $title = $data['title'] ?? 'Notification';
    $message = $data['message'] ?? 'You have a new notification.';

    if (!$userId || !$action) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing parameters']);
        exit;
    }

    // --- Insert into MongoDB ---
    $notification = [
        'user_id' => $userId,
        'property_id' => $propertyId,
        'action' => $action,
        'title' => $title,
        'message' => $message,
        'status' => 'new',
        'created_at' => new MongoDB\BSON\UTCDateTime()
    ];

    $result = $trippNotifications->insertOne($notification);

    // --- Publish to Ably for real-time updates ---
    $channelName = "user:notifications:$userId";
    try {
        $ably->channel($channelName)->publish('new-notification', [
            'id' => (string)$result->getInsertedId(),
            'title' => $title,
            'message' => $message,
            'action' => $action,
            'property_id' => $propertyId,
            'status' => 'new',
            'timeAgo' => 'just now'
        ]);
    } catch (Exception $e) {
        error_log("Ably publish failed: " . $e->getMessage());
    }

    echo json_encode(['success' => true, 'notification_id' => (string)$result->getInsertedId()]);
