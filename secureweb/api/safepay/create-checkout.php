<?php
/**
 * Safepay Checkout Creation Endpoint for Secure Web
 * Path: /var/www/doubbletech.com/secureweb/api/safepay/create-checkout.php
 */

// --- 1. CORS & SECURITY HEADERS ---
// These headers allow your Chrome Extension to make requests to this script
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

// Handle the preflight "OPTIONS" request sent by Chrome
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- 2. CONFIGURATION ---
// Your updated Secret Key
$SAFEPAY_SECRET_KEY = "f8d0c3948a131b8d68c816e4053350931a4449f7ce604bba17e037e12b843136"; 
// Your Sandbox Public Key
$MERCHANT_GUID = "sec_f4e6c571-96c6-48ca-88f8-386f3777aa42"; 

$SANDBOX_API_URL = "https://sandbox.api.getsafepay.com/v1/checkout/tracker";
$CHECKOUT_BASE_URL = "https://sandbox.api.getsafepay.com/checkout/pay";

// --- 3. PROCESS THE REQUEST ---
// Get JSON data sent from the Chrome Extension popup
$input = json_decode(file_get_contents("php://input"), true);

$amount = $input['amount'] ?? 299;
$phone  = $input['phone'] ?? '';
$email  = $input['email'] ?? '';
$plan   = $input['plan'] ?? 'Secure Web Subscription';

// Basic Validation
if (empty($phone) || empty($email)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing user details"]);
    exit;
}

// --- 4. CALL SAFEPAY API ---
$payload = [
    "amount" => (int)$amount,
    "currency" => "PKR",
    "merchant_guid" => $MERCHANT_GUID,
    "name" => $plan,
    "description" => "Subscription for account: " . $phone,
    "client_token" => $phone // We use phone number as a reference for the webhook later
];

$ch = curl_init($SANDBOX_API_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "X-SFPY-MERCHANT-SECRET: " . $SAFEPAY_SECRET_KEY
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// --- 5. RESPOND TO EXTENSION ---
if ($httpCode === 200) {
    $result = json_decode($response, true);
    if (isset($result['data']['token'])) {
        $token = $result['data']['token'];
        
        // Generate the secure URL to send back to popup.js
        $checkoutUrl = $CHECKOUT_BASE_URL . "?beacon=" . $token . "&env=sandbox";

        echo json_encode([
            "status" => "success",
            "checkoutUrl" => $checkoutUrl
        ]);
    } else {
        echo json_encode([
            "status" => "error", 
            "message" => "Could not retrieve tracker token from Safepay."
        ]);
    }
} else {
    // Return the error for debugging in the Inspect Console
    echo json_encode([
        "status" => "error",
        "message" => "Safepay API returned code " . $httpCode,
        "details" => json_decode($response, true),
        "curl_error" => $curlError
    ]);
}
?>
