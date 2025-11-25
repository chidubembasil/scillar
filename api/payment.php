<?php
    require "configs.php";

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require "../vendor/autoload.php";

    header("Content-Type: application/json");

    // Subaccounts
    $PERCENT_SUBACCOUNT = "RS_A83B219334DD5EC356BA7DB99E38933F";
    $FLAT_SUBACCOUNT = "RS_08C55A89BC9509676E1A38FC95B4BC93";

    $REDIRECT_URL = "https://yourwebsite.com/payment-success";

    // Allow only POST
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        echo json_encode(["status" => "error", "message" => "Only POST allowed"]);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true);

    $propertyId = $data["propertyId"] ?? "";
    $userEmail = $data["email"] ?? "";
    $currency = strtoupper($data["currency"] ?? "NGN");

    if (!$propertyId || !$userEmail) {
        echo json_encode(["status" => "error", "message" => "Missing email or propertyId"]);
        exit;
    }

    // Fetch property
    $property = $trippListing->findOne(["_id" => $propertyId]);

    if (!$property) {
        echo json_encode(["status" => "error", "message" => "Property not found"]);
        exit;
    }

    $amount = $property["price"];
    $type = strtolower($property["type"]);
    $ownerId = $property["ownerId"] ?? "unknown";

    // Select subaccount
    $selectedSubaccount = ($type === "buy") ? $PERCENT_SUBACCOUNT : $FLAT_SUBACCOUNT;

    // Create payload
    $payload = [
        "tx_ref" => "TRIPP_" . uniqid(),
        "amount" => $amount,
        "currency" => $currency,
        "redirect_url" => $REDIRECT_URL,
        "customer" => [
            "email" => $userEmail
        ],
        "customizations" => [
            "title" => "Tripp Property Payment",
            "description" => "Payment for property: " . ($property["title"] ?? "Real Estate Deal")
        ],
        "subaccounts" => [
            ["id" => $selectedSubaccount]
        ]
    ];

    //    Call Flutterwave
    $ch = curl_init("https://api.flutterwave.com/v3/payments");

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . FLW_SECRET_KEY
    ]);

    $response = curl_exec($ch);

    if (function_exists("curl_close")) curl_close($ch);

    $res = json_decode($response, true);

    if (!$res || $res["status"] !== "success") {
        echo json_encode([
            "status" => "error",
            "message" => "Flutterwave error",
            "response" => $res
        ]);
        exit;
    }

    $paymentLink = $res["data"]["link"];

    // Insert transaction
    $trippTransaction->insertOne([
        "tx_ref" => $payload["tx_ref"],
        "propertyId" => $propertyId,
        "ownerId" => $ownerId,
        "email" => $userEmail,
        "amount" => $amount,
        "currency" => $currency,
        "type" => $type,
        "subaccount_used" => $selectedSubaccount,
        "status" => "pending",
        "createdAt" => date("Y-m-d H:i:s")
    ]);


    // --------------------------------------------
    //  SEND EMAIL WITH TRANSACTION DETAILS
    // --------------------------------------------
    try {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = "smtp.gmail.com";
        $mail->SMTPAuth = true;

        // YOUR EMAIL + APP PASSWORD
        $mail->Username = "yourgmail@gmail.com";
        $mail->Password = "your-app-password";

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom("yourgmail@gmail.com", "Tripp Real Estate");
        $mail->addAddress($userEmail);

        $mail->isHTML(true);
        $mail->Subject = "Your Tripp Payment Link & Transaction Details";

        $mail->Body = "
            <h2>Your Payment Details</h2>
            <p>Thank you for choosing Tripp.</p>
            <p><strong>Property:</strong> {$property['title']}</p>
            <p><strong>Amount:</strong> {$currency} {$amount}</p>
            <p><strong>Type:</strong> {$type}</p>
            <p><strong>Transaction Reference:</strong> {$payload['tx_ref']}</p>
            <p><a href='$paymentLink'>Click here to complete your payment</a></p>
            <br>
            <p>Regards,<br>Tripp Team</p>
        ";

        $mail->send();

    } catch (Exception $e) {
        // Don't block payment if email fails
    }


    echo json_encode([
        "status" => "success",
        "message" => "Payment created",
        "payment_link" => $paymentLink
    ]);
    exit;

