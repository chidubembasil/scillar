<?php
    include "configs.php";
    use Ably\AblyRest;

    // Ably connection
    $client = new AblyRest("RSTb1g.vchEGQ:QDy0r8L70mwgsHpNtXNlWZ4DIN661iLkMCnI_7ELMDA");
    $channel = $client->channels->get("rent");

    if($_SERVER["REQUEST_METHOD"] === "POST") {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data["id"] ?? '';

        if(empty($id)) {
            echo json_encode(["status"=> "error", "message"=> "ID is required"]);
            exit;
        }

        // Fetch the property
        $rent = $trippListing->findOne([
            'Listing_id' => $id
        ]);

        if($rent) {
            // Convert to array for Ably and JSON
            $rentArray = json_decode(json_encode($rent), true);
            $channel->publish("rents", $rentArray);

            echo json_encode([
                "status" => "success",
                "message" => "Property found",
                "data" => $rentArray
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
