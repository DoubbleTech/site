<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- CONFIGURATION ---
// REPLACE with your NEW Secret Key from Safepay Sandbox (the one you refreshed)
$SAFEPAY_SECRET_KEY = "REPLACE_WITH_YOUR_NEW_ACTUAL_SECRET_KEY"; 
$SANDBOX_API_URL = "https://sandbox.api.getsafepay.com/v1/checkout/tracker";
$CHECKOUT_BASE_URL = "https://sandbox.api.getsafepay.com/checkout/pay";

// 1. Get data from the Chrome Extension
$input = json_decode(file_get_contents("php://input"), true);
$amount = $input['amount'] ?? 299;
$phone = $input['phone'] ?? '';
$email = $input['email'] ?? '';

if (!$phone || !$email) {
    echo json_encode(["error" => "Missing user details"]);
    exit;
}

// 2. Prepare the payload for Safepay
$data = [
    "amount" => $amount,
    "currency" => "PKR",
    "merchant_guid" => "sec_f4e6c571-96c6-48ca-88f8-386f3777aa42", // Your Public Key from screenshot
    "name" => "Secure Web - Full Plan",
    "description" => "Subscription for " . $phone,
    "client_token" => $phone // We use the phone number as a unique reference
];

// 3. Request a Checkout Tracker Token from Safepay
$ch = curl_init($SANDBOX_API_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "X-SFPY-MERCHANT-SECRET: " . $SAFEPAY_SECRET_KEY
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

// 4. Generate the final URL and send it back to the Extension
if ($httpCode === 200 && isset($result['data']['token'])) {
    $token = $result['data']['token'];
    
    // Construct the hosted checkout URL
    $checkoutUrl = $CHECKOUT_BASE_URL . 
                   "?beacon=" . $token . 
                   "&env=sandbox";

    echo json_encode(["checkoutUrl" => $checkoutUrl]);
} else {
    echo json_encode([
        "error" => "Safepay API Error", 
        "details" => $result,
        "code" => $httpCode
    ]);
}
?>
