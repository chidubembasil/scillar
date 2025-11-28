<?php
// auth.php
// Smart, robust auth endpoint (login, register, forgot, delete) + profile update (PUT)
// Uses MongoDB collections provided by configs.php
// -----------------------------------------------------------------------------

;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTimen as UTCDateTime;


// Start session only if not already started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Load configs (should set up $scillarUser, etc.)
require_once __DIR__ . '/configs.php';

// Standard headers
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// Allow preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Helper: send JSON response and exit
function send_json($payload, $code = 200) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

// Read incoming JSON (works for POST/PUT/DELETE requests)
$raw = file_get_contents("php://input");
$input = json_decode($raw, true);

// Also allow form-encoded POST fallback
if (!is_array($input) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = $_POST;
}
if (!is_array($input)) {
    $input = [];
}

// read mode if present
$mode = $input['mode'] ?? null;

// Basic validation helper
function val_email($e) {
    return filter_var($e, FILTER_VALIDATE_EMAIL) ? strtolower(trim($e)) : false;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];

    // -----------------------
    // POST: login / register / forgot / delete
    // -----------------------
    if ($method === 'POST') {
        // Ensure mode provided for POST flows; default to 'login' for backwards compat
        $mode = $mode ?? 'login';

        // Common fields
        $emailRaw = $input['email'] ?? '';
        $email = val_email($emailRaw);
        $password = $input['password'] ?? '';

        // ---------------- FORGOT ----------------
        if ($mode === 'forgot') {
            if (!$email) send_json(["status" => "error", "message" => "Valid email is required"], 400);

            $user = $scillarUser->findOne(["email" => $email]);
            if (!$user) send_json(["status" => "error", "message" => "Email not found"], 404);

            // Generate new password (8 chars)
            $newPass = substr(str_shuffle("ABCDEFGHJKMNPQRSTUVWXYZ23456789"), 0, 8);
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);

            $update = $scillarUser->updateOne(["email" => $email], ['$set' => ["password" => $hashed]]);
            // send email
            try {
                require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
                require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
                require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';

                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = "smtp.gmail.com";
                $mail->SMTPAuth   = true;
                $mail->Username   = "yourgmail@gmail.com"; // CHANGE
                $mail->Password   = "your-app-password";   // CHANGE
                $mail->SMTPSecure = "tls";
                $mail->Port       = 587;
                $mail->setFrom("no-reply@scillar.com", "scillar Support");
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = "Your Password Reset - scillar";
                $mail->Body = "
                    <h3>Password Reset Successful</h3>
                    <p>Your new password is:</p>
                    <h2>{$newPass}</h2>
                    <p>Please log in and change it immediately.</p>
                ";
                $mail->send();
            } catch (Exception $e) {
                // Log error server-side (if you have logging)
                // Do not fail the entire flow if email sending fails
            }

            send_json(["status" => "success", "message" => "A new password has been generated and emailed (if possible)"]);
        }

        // ---------------- LOGIN ----------------
        if ($mode === 'login') {
            if (!$email || !$password) send_json(["status" => "error", "message" => "Email & password required"], 400);

            $user = $scillarUser->findOne(["email" => $email]);
            if (!$user) send_json(["status" => "error", "message" => "User not found"], 404);

            // NOTE: some documents may store password as plain (bad) — expects hashed.
            if (!isset($user['password']) || !password_verify($password, $user['password'])) {
                send_json(["status" => "error", "message" => "Invalid password"], 401);
            }

            // Set session info
            $_SESSION['user_id'] = (string)($user['_id'] ?? $user['_id']);
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'] ?? '';

            send_json([
                "status" => "success",
                "message" => "Login successful",
                "user" => [
                    "id" => (string)$user["_id"],
                    "email" => $user["email"],
                    "name" => $user["name"] ?? ""
                ]
            ]);
        }

        // ---------------- REGISTER ----------------
        if ($mode === 'register') {
            if (!$email || !$password) send_json(["status" => "error", "message" => "Email & password required"], 400);

            $exists = $scillarUser->findOne(["email" => $email]);
            if ($exists) send_json(["status" => "error", "message" => "Email already exists"], 409);

            $username = explode("@", $email)[0];
            $name = ucwords(preg_replace("/[\._\d]/", " ", $username));
            $userId = uniqid("user_", true);

            $insertDoc = [
                "_id" => $userId,
                "name" => $name,
                "email" => $email,
                "password" => password_hash($password, PASSWORD_DEFAULT),
                "created_at" => new MongoDB\BSON\UTCDateTime()
            ];

            $insert = $scillarUser->insertOne($insertDoc);
    
            if ( $insert->getInsertedCount() > 0) {
                // Set session
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $name;

                send_json(["status" => "success", "message" => "User registered successfully", "user" => ["id" => $userId, "email" => $email, "name" => $name]], 201);
            } else {
                // For older driver versions, check insertedId
                if (!empty($insert->getInsertedId())) {
                    send_json(["status" => "success", "message" => "User registered successfully", "user" => ["id" => (string)$insert->getInsertedId(), "email" => $email, "name" => $name]], 201);
                }
                send_json(["status" => "error", "message" => "Failed to register user"], 500);
            }
        }

        // ---------------- DELETE (account) ----------------
        if ($mode === 'delete') {
            if (!$email || !$password) send_json(["status" => "error", "message" => "Email & password required to delete account"], 400);

            $user = $scillarUser->findOne(["email" => $email]);
            if (!$user) send_json(["status" => "error", "message" => "User not found"], 404);

            if (!isset($user['password']) || !password_verify($password, $user['password'])) {
                send_json(["status" => "error", "message" => "Invalid password"], 401);
            }

            $deleteResult = $scillarUser->deleteOne(["email" => $email]);
            if ($deleteResult->getDeletedCount() > 0) {
                // destroy session if this user was logged in
                if (isset($_SESSION['user_email']) && $_SESSION['user_email'] === $email) {
                    session_unset();
                    session_destroy();
                }
                send_json(["status" => "success", "message" => "Account deleted successfully"]);
            } else {
                send_json(["status" => "error", "message" => "Failed to delete account"], 500);
            }
        }

        // unknown mode — return error
        send_json(["status" => "error", "message" => "Invalid mode for POST"], 400);
    }

    // -----------------------
    // PUT: update profile
    // -----------------------
    if ($method === 'PUT') {
        $data = $input;

        $emailRaw = $data['email'] ?? '';
        $email = val_email($emailRaw);
        if (!$email) send_json(["status" => "error", "message" => "Email is required"], 400);

        // Required fields
        $required = ['phoneNo','bio','address','city','state','country','zipCode'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                send_json(["status"=>"error","message"=>"All fields are required: missing $field"], 400);
            }
        }

        $updateData = [
            'phoneNo' => $data['phoneNo'],
            'bio' => $data['bio'],
            'address' => $data['address'],
            'city' => $data['city'],
            'state' => $data['state'],
            'country' => $data['country'],
            'zipCode' => $data['zipCode'],
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ];

        $update = $scillarUser->updateOne(['email' => $email], ['$set' => $updateData]);

        if ($update->getModifiedCount() > 0) {
            send_json(["status"=>"success","message"=>"User updated successfully"]);
        } else {
            // If nothing modified, but user exists return message accordingly
            $exists = $scillarUser->findOne(['email' => $email]);
            if ($exists) {
                send_json(["status"=>"success","message"=>"No changes were made"]);
            } else {
                send_json(["status"=>"error","message"=>"User not found"], 404);
            }
        }
    }

    // -----------------------
    // GET: optional convenience (get current session user)
    // -----------------------
    if ($method === 'GET') {
        if (isset($_SESSION['user_email'])) {
            $u = $scillarUser->findOne(['email' => $_SESSION['user_email']]);
            if ($u) {
                send_json(["status"=>"success","user" => [
                    "id" => (string)$u["_id"],
                    "email" => $u["email"],
                    "name" => $u["name"] ?? ""
                ]]);
            }
        }
        send_json(["status"=>"error","message"=>"Not authenticated"], 401);
    }

    // fallback
    send_json(["status"=>"error","message"=>"Method not allowed"], 405);

} catch (Throwable $e) {
    // return error message (in production, consider logging and return generic message)
    $msg = $e->getMessage();
    send_json(["status"=>"error","message"=>"Server error: " . $msg], 500);
}
    