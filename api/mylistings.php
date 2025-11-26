<?php
    session_start();
    include "configs.php";
    include "upload.php";
    use Ably\AblyRest;
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

    // Ably connection
    $client = new AblyRest("RSTb1g.vchEGQ:QDy0r8L70mwgsHpNtXNlWZ4DIN661iLkMCnI_7ELMDA");
    $channel = $client->channels->get("mylistings");

    // POST: Add new listing
    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        $data = json_decode(file_get_contents("php://input"), true);

        // Get user ID from request
        $userId = $data["userId"] ?? null;

        if (!$userId) {
            echo json_encode([
                "status" => "error",
                "message" => "userId is required"
            ]);
            exit;
        }

        // Create listing object
        $listing = [
            "userId" => $userId,
            "Listing_id" => $listingId,
            "title" => $PropTitle,
            "description" => $PropDesc,
            "address" => $address,
            "city" => $city,
            "state" => $state,
            "country" => $country,
            "zipCode" => $zipCode,
            "price" => $price,
            "bedroom" => $bedroom,
            "bathroom" => $bathroom,
            "squareFeet" => $squareFeet,
            "yearBuilt" => $yearBuilt,
            "parkingSpot" => $parkingSpot,
            "email" => $email,

            // file upload values
            "image" => $image,
            "imageTmpname" => $imageTmpName,
            "imageName" => $imageName,
            "imageType" => $imageType,
            "imageSize" => $imageSize,
            "imageError" => $imageError,

            "createdAt" => time()
        ];

        // Insert to database
        $insertResult = $trippListing->insertOne($listing);

        // Add inserted ID
        $listing["_id"] = $insertResult->getInsertedId();

        // Publish realtime update
        $channel->publish("newListing", $listing);

        echo json_encode([
            "status" => "success",
            "message" => "Property uploaded successfully",
            "data" => $listing
        ]);
        exit;
    }



    // GET: Show listings
    if ($_SERVER["REQUEST_METHOD"] === "GET") {

        // Check if user wants a specific user's listings
        $userId = $_GET["userId"] ?? null;

        if ($userId) {
            // Only this user's listings
            $listings = $trippListing->find(
                ["userId" => $userId],
                ["sort" => ["_id" => -1]]
            );
        } else {
            // All listings
            $listings = $trippListing->find(
                [],
                ["sort" => ["_id" => -1]]
            );
        }

        echo json_encode([
            "status" => "success",
            "data" => iterator_to_array($listings)
        ]);
        exit;
    }

