<?php
// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Load PHPMailer files
require 'Exception.php';
require 'PHPMailer.php';
require 'SMTP.php';

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Sanitize and get form data
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $subject = htmlspecialchars($_POST['subject']);
    $message = htmlspecialchars($_POST['message']);

    // --- Create the email body ---
    $body = "You have received a new message from your website contact form.\n\n";
    $body .= "Here are the details:\n\n";
    $body .= "Name: " . $name . "\n";
    $body .= "Email: " . $email . "\n";
    $body .= "Subject: " . $subject . "\n";
    $body .= "Message: \n" . $message . "\n";
    // -----------------------------

    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // --- Server Settings (Hostinger) ---
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;  // Uncomment this line to see detailed error messages
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@doubbletech.com'; // Your full email address
        $mail->Password   = 'YOUR_EMAIL_PASSWORD_HERE'; // !!! REPLACE THIS with your email password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        // ---------------------------------

        // --- Recipients ---
        $mail->setFrom('info@doubbletech.com', $name); // Who the email is FROM (use your email, but with the sender's name)
        $mail->addAddress('info@doubbletech.com', 'DoubbleTech Admin'); // Who the email is TO
        $mail->addReplyTo($email, $name); // Set the 'Reply-To' to the person who filled out the form
        // ------------------

        // --- Content ---
        $mail->isHTML(false); // Set email format to plain text
        $mail->Subject = 'New Contact Form Message: ' . $subject;
        $mail->Body    = $body;
        // ---------------

        $mail->send();
        echo 'Thank you for contacting us!';
    } catch (Exception $e) {
        // If it fails, show the error.
        // For debugging, you can use: echo "Sorry, your message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        echo "Sorry, your message could not be sent.";
    }
} else {
    // Not a POST request
    echo "There was a problem with your submission.";
}
?>
