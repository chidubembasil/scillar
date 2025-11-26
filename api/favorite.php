<?php
    session_start();
    include "configs.php";
    use Ably\AblyRest;

    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

    // Initialize Ably
    $ably = new AblyRest('RSTb1g.vchEGQ:QDy0r8L70mwgsHpNtXNlWZ4DIN661iLkMCnI_7ELMDA');
    $channel = $ably->channels->get('favorite');

    // Simulate logged-in user (replace with real session or auth token)
    $userId = $data['userId'] ?? 'guest_user';

    // ================= POST: ADD TO FAVORITES =================
    if ($_SERVER['REQUEST_METHOD'] === "POST") {
        $data = json_decode(file_get_contents("php://input"), true);

        $listingId = $data['Listing_id'] ?? null;
        if (!$listingId) {
            echo json_encode([
                "status" => "error",
                "message" => "Listing ID is required"
            ]);
            exit;
        }

        // Prevent duplicate favorites
        $existing = $trippFav->findOne([
            "Listing_id" => $listingId,
            "userId" => $userId
        ]);

        if ($existing) {
            echo json_encode([
                "status" => "error",
                "message" => "Property already in favorites"
            ]);
            exit;
        }

        // Fetch the property
        $property = $trippListing->findOne([
            "Listing_id" => $listingId
        ]);

        if (!$property) {
            echo json_encode([
                "status" => "error",
                "message" => "Property not found"
            ]);
            exit;
        }

        // Convert to array & remove _id
        $propertyArr = json_decode(json_encode($property), true);
        unset($propertyArr['_id']);

        // Add userId to track who favorited
        $propertyArr['userId'] = $userId;
        $propertyArr['favoritedAt'] = time();

        // Insert into favorites
        $insert = $trippFav->insertOne($propertyArr);

        // Publish real-time update via Ably
        $channel->publish("favorite", [
            "action" => "added",
            "Listing_id" => $listingId,
            "userId" => $userId
        ]);

        echo json_encode([
            "status" => "success",
            "message" => "Property added to favorites",
            "data" => [
                "insertedId" => (string)$insert->getInsertedId()
            ]
        ]);
        exit;
    }

    // ================= DELETE: REMOVE FROM FAVORITES =================
    if ($_SERVER['REQUEST_METHOD'] === "DELETE") {
        $data = json_decode(file_get_contents("php://input"), true);

        $listingId = $data['Listing_id'] ?? null;
        if (!$listingId) {
            echo json_encode([
                "status" => "error",
                "message" => "Listing ID is required"
            ]);
            exit;
        }

        // Delete favorite by listingId + userId
        $delete = $trippFav->deleteOne([
            "Listing_id" => $listingId,
            "userId" => $userId
        ]);

        if ($delete->getDeletedCount() === 0) {
            echo json_encode([
                "status" => "error",
                "message" => "Favorite not found"
            ]);
            exit;
        }

        // Publish real-time update via Ably
        $channel->publish("favorite", [
            "action" => "removed",
            "Listing_id" => $listingId,
            "userId" => $userId
        ]);

        echo json_encode([
            "status" => "success",
            "message" => "Property removed from favorites",
            "data" => [
                "deletedCount" => $delete->getDeletedCount()
            ]
        ]);
        exit;
    }

    // ================= GET: LIST FAVORITES =================
    if ($_SERVER['REQUEST_METHOD'] === "GET") {
        // Return all favorites for the user
        $favorites = $trippFav->find([
            "userId" => $userId
        ], ['sort' => ['favoritedAt' => -1]]);

        echo json_encode([
            "status" => "success",
            "data" => iterator_to_array($favorites)
        ]);
        exit;
    }
