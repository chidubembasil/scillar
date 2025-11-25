<?php
    require "configs.php";

    header('Content-Type: application/json');
    $userId = $_GET['user_id'] ?? '';

    if (!$userId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing user_id']);
        exit;
    }

    // --- Fetch notifications ---
    $cursor = $trippNotifications->find(
        ['user_id' => $userId],
        ['sort' => ['created_at' => -1]]
    );

    $notifications = [];
    foreach ($cursor as $doc) {
        $notifications[] = [
            'id' => (string)$doc['_id'],
            'title' => $doc['title'],
            'message' => $doc['message'],
            'action' => $doc['action'],
            'property_id' => $doc['property_id'] ?? null,
            'status' => $doc['status'],
            'timeAgo' => 'just now'
        ];
    }

    echo json_encode($notifications);
