<?php
    session_start();
    include "configs.php";
    include "auth.php";

    use Ably\AblyRest;


    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

    // INIT ABLY
    $ably = new AblyRest('RSTb1g.vchEGQ:QDy0r8L70mwgsHpNtXNlWZ4DIN661iLkMCnI_7ELMDA');
    $channel = $ably->channels->get('mylistings');

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        $data = $_POST;

        // Generate Unique ID
        $listingId = uniqid();

        // Sanitize inputs
        $ListingType     = trim($data["ListingType"] ?? '');
        $propTitle       = trim($data["propertyTitle"] ?? '');
        $propDesc        = trim($data["propertyDescription"] ?? '');
        $address         = trim($data["address"] ?? '');
        $city            = trim($data["city"] ?? '');
        $state           = trim($data["state"] ?? '');
        $country         = trim($data["country"] ?? '');
        $zipCode         = trim($data["zipCode"] ?? '');
        $price           = trim($data["price"] ?? '');
        $bedroom         = trim($data["bedroom"] ?? '');
        $bathroom        = trim($data["bathroom"] ?? '');
        $squareFeet      = trim($data["squareFeet"] ?? '');
        $yearBuilt       = trim($data["yearBuilt"] ?? '');
        $parkingSpot     = trim($data["parkingSpot"] ?? '');
        $investmentPlan  = trim($data["investmentPlan"] ?? '');
        $rentalDuration  = trim($data["rentalDuration"] ?? '');
        $currency  = trim($data["currency"] ?? '');

        // --- REQUIRED FIELDS ---
        $requiredFields = [
            $propTitle, $propDesc, $address, $city, $state,
            $country, $zipCode, $price, $bedroom, $bathroom,
            $squareFeet, $parkingSpot, $yearBuilt, $ListingType, $currency
        ];

        // General required check
        if (in_array('', $requiredFields)) {
            echo json_encode([
                "status" => "error",
                "message" => "All fields are required"
            ]);
            exit;
        }

        // Listing type specific
        if ($ListingType === "invest" && empty($investmentPlan)) {
            echo json_encode([
                "status" => "error",
                "message" => "Investment plan is required"
            ]);
            exit;
        }

        if ($ListingType === "rent" && empty($rentalDuration)) {
            echo json_encode([
                "status" => "error",
                "message" => "Rental duration is required"
            ]);
            exit;
        }

        // --- IMAGES VALIDATION ---
        if (!isset($_FILES["propertyImg"])) {
            echo json_encode([
                "status" => "error",
                "message" => "At least one property image is required"
            ]);
            exit;
        }

        $images = [];
        $imageFiles = $_FILES['propertyImg'];

        for ($i = 0; $i < count($imageFiles['name']); $i++) {

            $tmpName = $imageFiles['tmp_name'][$i];
            $error   = $imageFiles['error'][$i];

            if ($error === 0) {
                $newName = uniqid() . "_" . basename($imageFiles['name'][$i]);
                $uploadPath = __DIR__ . "/uploads/" . $newName;

                move_uploaded_file($tmpName, $uploadPath);

                $images[] = [
                    "imageName" => $newName,
                    "imagePath" => "uploads/" . $newName,
                    "imageType" => $imageFiles['type'][$i],
                    "imageSize" => $imageFiles['size'][$i]
                ];
            }
        }

        // --- INSERT INTO MONGODB ---
        $upload = $scillarProperty->insertOne([
            "Listing_id"    => $listingId,
            "title"         => $propTitle,
            "type"          => $ListingType,
            "description"   => $propDesc,
            "address"       => $address,
            "city"          => $city,
            "state"         => $state,
            "country"       => $country,
            "zipCode"       => $zipCode,
            "currency"      => $currency,
            "price"         => $price,
            "bedroom"       => $bedroom,
            "bathroom"      => $bathroom,
            "squareFeet"    => $squareFeet,
            "yearBuilt"     => $yearBuilt,
            "parkingSpot"   => $parkingSpot,
            "images"        => $images,
            "investmentPlan" => $investmentPlan,
            "rentalDuration" => $rentalDuration,
            "createdAt"     => time()
        ]);

        if ($upload) {

            // --- SEND REAL-TIME UPDATE TO ABLY ---
            $channel->publish("update", [
                "action"      => "new_listing",
                "Listing_id"  => $listingId,
                "title"       => $propTitle,
                "images"      => $images
            ]);

            echo json_encode([
                "status" => "success",
                "message" => "Property uploaded successfully",
                "data" => [
                    "Listing_id" => $listingId,
                    "title" => $propTitle,
                    "images" => $images
                ]
            ]);

        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Property upload failed"
            ]);
        }

    }

