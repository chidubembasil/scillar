<?php
    require "configs.php";

    header('Content-Type: application/json');
    $userId = $_POST['user_id'] ?? '';

    if (!$userId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing user_id']);
        exit;
    }

    // --- Update all new notifications to read ---
    $trippNotifications->updateMany(
        ['user_id' => $userId, 'status' => 'new'],
        ['$set' => ['status' => 'read']]
    );

    echo json_encode(['success' => true]);
