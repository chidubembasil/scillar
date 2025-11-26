<?php
    session_start();
    require "../vendor/autoload.php";

    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

    // === MongoDB Credentials (Hardcoded for now) ===
    // Replace these later with environment variables
    $mongoUser = "okoro9115_db_user";
    $mongoPass = "xihim7jikYx55APu";  // IMPORTANT: Reset this since it's exposed publicly!

    // Encode password (required if it contains special chars)
    $mongoPassEncoded = urlencode($mongoPass);

    // === Build Connection URI properly ===
    $mongoUri = "mongodb+srv://{$mongoUser}:{$mongoPassEncoded}@scillar.2y1ablb.mongodb.net/?retryWrites=true&w=majority&appName=scillar";

    // === Connect ===
    try {
        $client = new MongoDB\Client($mongoUri);

        // Select database and collections
        $db = $client->scillar;
        $scillarUser = $db->users;
        $scillarTransaction = $db->transactions;
        $scillarListing = $db->listing;
        $scillarFav = $db->favourite;
        $scillarNotifications = $db->notification;
        $scillarKyc = $db->kyc;

    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => "MongoDB connection failed",
            "error" => $e->getMessage()
        ]);
        exit;
    }

    // === Flutterwave Secret Key ===
    define("FLW_SECRET_KEY", "LhQ6zjwLvbLJXxPEWNtyoCXYusAGOkQE");

    // === Ably ===
    define('ABLY_API_KEY', 'RSTb1g.Dg9vCg:IYEo1Otd0e1OLvKynv_go5Ma3LvCEa2R1ln7KLwhRk8');
    $ably = new Ably\AblyRest(ABLY_API_KEY);


