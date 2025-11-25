<?php
    include "config.php";
    use MongoDB\BSON\Regex; // make sure this is included

    if($_SERVER["REQUEST_METHOD"] === "POST"){
        $data = json_decode(file_get_contents("php://input"), true);
        $search = $data["search"] ?? "";

        if(empty($search)){
            echo json_encode([
                "status"=>"error",
                "message"=>"Search term cannot be empty"
            ]);
            exit;
        }

        // Create case-insensitive regex
        $regex = new Regex($search, "i");

        // Query trippListings collection
        $listings = $trippListings->find([
            '$or' => [
                ["address" => $regex],
                ["city" => $regex],
                ["state" => $regex],
                ["country" => $regex]
            ]
        ], ["sort" => ["_id" => -1]]); // newest first

        echo json_encode([
            "status"=>"success",
            "data"=>iterator_to_array($listings)
        ]);
        exit;
    }

    // Optional: handle GET to fetch all listings
    if($_SERVER["REQUEST_METHOD"] === "GET"){
        $listings = $trippListings->find([], ["sort"=>["_id"=>-1]]);
        echo json_encode([
            "status"=>"success",
            "data"=>iterator_to_array($listings)
        ]);
        exit;
    }
