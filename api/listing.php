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
    $channel = $ably->channels->get('mylistings');


    // ======================= PAGINATION ===========================
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

    if ($page < 1) $page = 1;
    if ($limit < 1) $limit = 10;

    $skip = ($page - 1) * $limit;
    // ===============================================================



    /* ============================================================
    POST — SEARCH LISTINGS (Paginated)
    ============================================================ */
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        $data = json_decode(file_get_contents("php://input"), true);

        $type = trim($data['type'] ?? '');
        $country = trim($data['country'] ?? '');
        $city = trim($data['city'] ?? '');
        $state = trim($data['state'] ?? '');
        $price = trim($data['price'] ?? '');
        $bed = trim($data['bed'] ?? '');
        $bath = trim($data['bath'] ?? '');

        // Pick city/state
        $locationField = '';
        $locationValue = '';

        if (!empty($city)) {
            $locationField = 'city';
            $locationValue = $city;
        } elseif (!empty($state)) {
            $locationField = 'state';
            $locationValue = $state;
        }

        // required
        if (
            empty($type) || empty($country) || empty($locationValue) ||
            empty($price) || empty($bed) || empty($bath)
        ) {
            echo json_encode([
                "status" => "error",
                "message" => "All fields are required"
            ]);
            exit;
        }

        // Build query
        $query = [
            'type' => $type,
            'country' => $country,
            $locationField => $locationValue,
            'price' => $price,
            'bed' => $bed,
            'bath' => $bath
        ];

        // Find paginated
        $cursor = $trippListing->find(
            $query,
            [
                "sort" => ["_id" => -1],
                "skip" => $skip,
                "limit" => $limit
            ]
        );

        $results = iterator_to_array($cursor);

        // Count for pagination
        $totalDocs = $trippListing->countDocuments($query);
        $totalPages = ceil($totalDocs / $limit);

        // Publish to Ably
        $channel->publish('searchResults', [
            "query" => $query,
            "results" => $results,
            "pagination" => [
                "page" => $page,
                "limit" => $limit,
                "totalDocs" => $totalDocs,
                "totalPages" => $totalPages
            ]
        ]);

        echo json_encode([
            "status" => "success",
            "data" => $results,
            "pagination" => [
                "page" => $page,
                "limit" => $limit,
                "totalDocs" => $totalDocs,
                "totalPages" => $totalPages
            ]
        ]);

        exit;
    }



    /* ============================================================
    GET — ALL LISTINGS (Paginated)
    ============================================================ */
    if ($_SERVER["REQUEST_METHOD"] == "GET") {

        $cursor = $trippListing->find(
            [],
            [
                "sort" => ["_id" => -1],
                "skip" => $skip,
                "limit" => $limit
            ]
        );

        $results = iterator_to_array($cursor);

        // Count all
        $totalDocs = $trippListing->countDocuments([]);
        $totalPages = ceil($totalDocs / $limit);

        echo json_encode([
            "status" => "success",
            "data" => $results,
            "pagination" => [
                "page" => $page,
                "limit" => $limit,
                "totalDocs" => $totalDocs,
                "totalPages" => $totalPages
            ]
        ]);
    }
