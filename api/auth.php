<?php

    session_start();
    include 'configs.php';

    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    header('Content-Type: application/json');

    // --- Handle POST (Login + Register + Forgot Password) ---
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        $data = json_decode(file_get_contents("php://input"), true);
        $mode = $data["mode"] ?? "login"; // login | register | forgot

        // Common fields
        $email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = $data['password'] ?? '';

        // ================
        // 1️⃣ FORGOT PASSWORD
        // ================
        if ($mode === "forgot") {

            if (empty($email)) {
                echo json_encode(["status" => "error", "message" => "Email is required"]);
                exit;
            }

            $user = $trippUser->findOne(["email" => $email]);

            if (!$user) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Email not found in system"
                ]);
                exit;
            }

            // Generate new password
            $newPass = substr(str_shuffle("ABCDEFGHJKMNPQRSTUVWXYZ23456789"), 0, 8);

            // Update in DB
            $trippUser->updateOne(
                ["email" => $email],
                ['$set' => ["password" => password_hash($newPass, PASSWORD_DEFAULT)]]
            );

            // Send Email using PHPMailer
            require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
            require '../vendor/phpmailer/phpmailer/src/SMTP.php';
            require '../vendor/phpmailer/phpmailer/src/Exception.php';

            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host       = "smtp.gmail.com";
                $mail->SMTPAuth   = true;
                $mail->Username   = "yourgmail@gmail.com"; // CHANGE
                $mail->Password   = "your-app-password";   // CHANGE
                $mail->SMTPSecure = "tls";
                $mail->Port       = 587;

                $mail->setFrom("no-reply@nome.com", "Tripp Support");
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = "Your Password Reset - Tripp";
                $mail->Body = "
                    <h3>Password Reset Successful</h3>
                    <p>Your new password is:</p>
                    <h2>$newPass</h2>
                    <p>Please log in and change it immediately.</p>
                ";

                $mail->send();

                echo json_encode([
                    "status" => "success",
                    "message" => "A new password has been sent to your email"
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Mailer Error: " . $mail->ErrorInfo
                ]);
            }

            exit;
        }

        // ================
        // 2️⃣ LOGIN
        // ================
        if ($mode === "login") {

            if (empty($email) || empty($password)) {
                echo json_encode(["status" => "error", "message" => "Email & password required"]);
                exit;
            }

            $user = $trippUser->findOne(["email" => $email]);

            if (!$user) {
                echo json_encode(["status" => "error", "message" => "User not found"]);
                exit;
            }

            if (!password_verify($password, $user['password'])) {
                echo json_encode(["status" => "error", "message" => "Invalid password"]);
                exit;
            }

            echo json_encode([
                "status" => "success",
                "message" => "Login successful",
                "user" => [
                    "id" => $user["_id"],
                    "email" => $user["email"],
                    "name" => $user["name"]
                ]
            ]);

           
            exit;
        }

        // ================
        // 3️⃣ REGISTER
        // ================
        if ($mode === "register") {

            if (empty($email) || empty($password)) {
                echo json_encode(["status" => "error", "message" => "Email & password required"]);
                exit;
            }

            $exists = $trippUser->findOne(["email" => $email]);

            if ($exists) {
                echo json_encode(["status" => "error", "message" => "Email already exists"]);
                exit;
            }

            // Convert email to display name
            $username = explode("@", $email)[0];
            $name = ucwords(preg_replace("/[\._\d]/", " ", $username));
            $userId = uniqid();

            $insert = $trippUser->insertOne([
                "_id" => $userId,
                "name" => $name,
                "email" => $email,
                "password" => password_hash($password, PASSWORD_DEFAULT)
            ]);

            // Set session after registration
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $name;

            echo json_encode([
                "status" => "success",
                "message" => "User registered successfully"
            ]);
            exit;
        }
    }

    // -----------------------------------------------------------
    // PUT = Update Profile
    // -----------------------------------------------------------
    if ($_SERVER["REQUEST_METHOD"] === "PUT") {

        $data = json_decode(file_get_contents("php://input"), true);
        $email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);

        if (empty($email)) {
            echo json_encode(["status" => "error", "message" => "Email is required"]);
            exit;
        }

        // Required fields
        $required = ['phoneNo','bio','address','city','state','country','zipCode'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                echo json_encode(["status"=>"error","message"=>"All fields are required"]);
                exit;
            }
        }

        // Update data
        $updateData = [
            'phoneNo' => $data['phoneNo'],
            'bio' => $data['bio'],
            'address' => $data['address'],
            'city' => $data['city'],
            'state' => $data['state'],
            'country' => $data['country'],
            'zipCode' => $data['zipCode'],
        ];

        $update = $trippUser->updateOne(
            ['email' => $email],
            ['$set' => $updateData]
        );

        if ($update->getModifiedCount() > 0) {
            echo json_encode(["status"=>"success","message"=>"User updated successfully"]);
        } else {
            echo json_encode(["status"=>"error","message"=>"No changes were made"]);
        }

        exit;
    }

    


    // No need for final echo with $success/$error
