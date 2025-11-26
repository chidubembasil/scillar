<?php
    session_start();
    require "configs.php";
    use Ably\AblyRest;
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

    // Ably setup
    $ably = new AblyRest('RSTb1g.vchEGQ:QDy0r8L70mwgsHpNtXNlWZ4DIN661iLkMCnI_7ELMDA');
    $channel = $ably->channels->get('kyc-updates');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $data = $_POST;
        $files = $_FILES;
        $userId = $data['userId'] ?? uniqid();
        $kycId = uniqid('kyc_');

        // ----------------------------
        // Store uploaded files
        // ----------------------------
        $uploads = [];
        $fileFields = [
            'uploadGovIDFront', 'uploadGovIDBack', 'selfieWithID',
            'propertyInsurance', 'bankStatement', 'proofOfFunds',
            'proofOfAddress', 'articlesOfIncorporation', 'operatingAgreement'
        ];
        foreach ($fileFields as $field) {
            if (!empty($files[$field]['name'])) {
                $ext = pathinfo($files[$field]['name'], PATHINFO_EXTENSION);
                $newName = uniqid() . "_$field.$ext";
                move_uploaded_file($files[$field]['tmp_name'], __DIR__ . "/uploads/$newName");
                $uploads[$field] = "uploads/$newName";
            }
        }

        // ----------------------------
        // Rule-based verification
        // ----------------------------
        $isVerified = true;
        $reasons = [];

        // Legal & Identity
        if (empty($data['ageLegal']) || empty($data['dateOfBirth']) || empty($data['idNumber'])) {
            $isVerified = false;
            $reasons[] = 'Legal & identity info incomplete';
        }
        $dob = strtotime($data['dateOfBirth'] ?? '');
        if ($dob && (time() - $dob) < 18 * 365*24*60*60) {
            $isVerified = false;
            $reasons[] = 'User must be at least 18';
        }
        foreach (['uploadGovIDFront','uploadGovIDBack','selfieWithID'] as $f) {
            if (empty($uploads[$f])) {
                $isVerified = false;
                $reasons[] = "$f missing";
            }
        }

        // Property Insurance
        if (empty($uploads['propertyInsurance'])) {
            $isVerified = false;
            $reasons[] = "Property insurance missing";
        }

        // Financial Verification
        if (empty($data['bankName']) || empty($data['accountType'])) {
            $isVerified = false;
            $reasons[] = "Bank details missing";
        }
        foreach (['bankStatement','proofOfFunds'] as $f) {
            if (empty($uploads[$f])) {
                $isVerified = false;
                $reasons[] = "$f missing";
            }
        }

        // Address Verification
        if (empty($data['residenceAddress']) || empty($data['city']) || empty($data['state']) || empty($data['zipCode'])) {
            $isVerified = false;
            $reasons[] = "Address info incomplete";
        }
        if (empty($uploads['proofOfAddress'])) {
            $isVerified = false;
            $reasons[] = "Proof of address missing";
        }

        // ----------------------------
        // Save to MongoDB
        // ----------------------------
        $trippKyc->insertOne(array_merge($data, $uploads, [
            'kycId' => $kycId,
            'userId' => $userId,
            'status' => $isVerified ? 'Verified' : 'Rejected',
            'reasons' => $reasons,
            'createdAt' => time()
        ]));

        // ----------------------------
        // Publish to Ably
        // ----------------------------
        $channel->publish('kyc-status', [
            'userId' => $userId,
            'kycId' => $kycId,
            'status' => $isVerified ? 'Verified' : 'Rejected',
            'reasons' => $reasons
        ]);

        echo json_encode([
            'status' => $isVerified ? 'success' : 'error',
            'kycStatus' => $isVerified ? 'Verified' : 'Rejected',
            'reasons' => $reasons
        ]);
    }
