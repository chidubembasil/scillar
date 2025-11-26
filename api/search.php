<?php
session_start();
include "configs.php";

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

use MongoDB\BSON\Regex;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    $search = $data["search"] ?? "";

    if (empty($search)) {
        echo json_encode([
            "status" => "error",
            "message" => "Search term cannot be empty"
        ]);
        exit;
    }

    $regex = new Regex($search, "i");

    // FIXED VARIABLE NAME
    $listings = $scillarListing->find([
        '$or' => [
            ["address" => $regex],
            ["city" => $regex],
            ["state" => $regex],
            ["country" => $regex]
        ]
    ], ["sort" => ["_id" => -1]]);

    echo json_encode([
        "status" => "success",
        "data" => iterator_to_array($listings)
    ]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    // FIXED VARIABLE NAME
    $listings = $scillarListing->find([], ["sort" => ["_id" => -1]]);

    echo json_encode([
        "status" => "success",
        "data" => iterator_to_array($listings)
    ]);
    exit;
}
