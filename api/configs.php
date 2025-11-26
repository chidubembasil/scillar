<?php
    session_start();
    require "../vendor/autoload.php";

    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

   /*  // Get environment variables
    $username = getenv('MONGO_USERNAME');
    $password = getenv('MONGO_PASSWORD');

    // Debug: check if PHP can read them
    var_dump($username);
    var_dump($password); */

    // URL-encode password in case it contains special characters
    $encodedPassword = urlencode($password);

    // Connect to MongoDB
    $client = new MongoDB\Client(
        "mongodb+srv://okoro9115_db_user:xihim7jikYx55APu@scillar.2y1ablb.mongodb.net/"
    );

    // Select database and collections
    $scillar = $client->scillar;
    $scillarUser = $scillar->users;
    $scillarTransaction = $scillar->transactions;
    $scillarListing = $scillar->listing;
    $scillarFav = $scillar->favourite;
    $scillarNotifications = $scillar ->notification;
    $scillarKyc = $scillar->kyc;

    try {
        $client->listDatabases();
        echo "Connected to MongoDB successfully!";
    } catch (Exception $e) {
        echo "Connection failed: " . $e->getMessage();
    }

    define('FLW_SECRET_KEY', 'LhQ6zjwLvbLJXxPEWNtyoCXYusAGOkQE');
    // Define your two subaccount IDs
 /*     define('SUBACCOUNT_PERCENTAGE', 'RS_A83B219334DD5EC356BA7DB99E38933F'); // for buy (percentage split)
        define('SUBACCOUNT_FLAT', 'RS_08C55A89BC9509676E1A38FC95B4BC93'); // for rent/invest/stay (flat split)
 */

        define('ABLY_API_KEY', 'RSTb1g.Dg9vCg:IYEo1Otd0e1OLvKynv_go5Ma3LvCEa2R1ln7KLwhRk8');
        $ably = new AblyRest(ABLY_API_KEY);