<?php
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

    $method = $_SERVER["REQUEST_METHOD"];
    $uri = explode("/", trim($_SERVER["REQUEST_URI"], "/"));

    // Example: /api/users → ["api", "users"]
    $endpoint = isset($uri[1]) ? $uri[1] : null;

    switch ($endpoint) {
        case "auth":
            require "api/auth.php";
            break;

        case "listing":
            require "api/listing.php";
            break;

        case "upload":
            require "api/upload.php";
            break;

        case "mylistings":
            require "api/mylistings.php";
            break;

        case "stay":
            require "api/stay.php";
            break;   
            
        case "rent":
            require "api/rent.php";
            break;    

        case "buy":
            require "api/buy.php";
            break;    
        
        case "invest":
            require "api/invest.php";
            break; 
            
        case "favorite":
            require "api/favorite.php";
            break;   
            
        case "mark":
            require "api/markAsRead.php";
            break;    
        
        case "create":
            require "api/notify-create.php";
            break;    

        case "search":
            require "api/rent.php";
            break;   
            
        case "fetch":
            require "api/notify-fetch.php";
            break;   
            
        case "kyc":
            require "api/kyc.php";
            break; 
            
        case "nessa":
            require "api/Nessa-Ai.php";
            break;        

        case "payment":
            require "api/payment.php";
            break;    

         
        default:
            echo json_encode(["error" => "Invalid endpoint"]);
    }
