<?php
    session_start();
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
            header("Location: api/auth.php");
            break;

        case "listing":
            header("Location: api/listing.php");
            break;

        case "upload":
            header("Location: api/upload.php");
            break;

        case "mylistings":
            header("Location: api/mylistings.php");
            break;

        case "stay":
            header("Location: api/stay.php");
            break;   
            
        case "rent":
            header("Location: api/rent.php");
            break;    

        case "buy":
            header("Location: api/buy.php");
            break;    
        
        case "invest":
            header("Location: api/invest.php");
            break; 
            
        case "favorite":
            header("Location: api/favorite.php");
            break;   
            
        case "mark":
            header("Location: api/markAsRead.php");
            break;    
        
        case "create":
            header("Location: api/notify-create.php");
            break;    

        case "search":
            header("Location: api/search.php");
            break;   
            
        case "fetch":
            header("Location: api/notify-fetch.php");
            
            break;   
            
        case "kyc":
            header("Location: api/kyc.php");
           
            break; 
            
        case "nessa":
            header("Location: api/Nessa-Ai.php");
            
            break;        

        case "payment":
            header("Location: api/payment.php");
            
            break;    

         
        default:
            echo json_encode(["error" => "Invalid endpoint"]);
    }
