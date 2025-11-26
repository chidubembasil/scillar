<?php
    session_start();
    include "configs.php";
    use Ably\AblyRest;
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

    // Ably connection
    $client = new AblyRest("RSTb1g.vchEGQ:QDy0r8L70mwgsHpNtXNlWZ4DIN661iLkMCnI_7ELMDA");
    $channel = $client->channels->get("invest");

    if($_SERVER["REQUEST_METHOD"] === "POST") {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data["id"] ?? '';

        if(empty($id)) {
            echo json_encode(["status"=> "error", "message"=> "ID is required"]);
            exit;
        }

        // Fetch the property
        $invest = $scillarListing->findOne([
            'Listing_id' => $id
        ]);

        if($invest) {
            // Convert to array for Ably and JSON
            $investArray = json_decode(json_encode($invest), true);
            $channel->publish("invests", $investArray);

            echo json_encode([
                "status" => "success",
                "message" => "Property found",
                "data" => $investArray
            ]);
            exit;
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Property not found"
            ]);
            exit;
        }
    }
