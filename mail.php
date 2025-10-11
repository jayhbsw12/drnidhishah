<?php
// mail.php — Appointment form via PHPMailer (Gmail SMTP) with DEV/PROD routing

error_reporting(E_ALL);
ini_set('display_errors', 0);

// ---- Load PHPMailer (paths must match your folder) ----
require __DIR__ . '/PHPMailer/Exception.php';
require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// =======================
// ENV / MODE SWITCH
// =======================
// Set to 'dev' while testing; switch to 'prod' when going live.
const MODE = 'prod';

// Define recipients for each mode
$RECIPIENTS = [
    // DEV: send to you + the new CC you wanted for testing
    'dev' => [
        'to' => [
            'jaymodihbsoftweb@gmail.com',              // You (testing)
        ],
        'cc' => [
            'info@hbsoftweb.com',   // Extra CC for dev
        ],
    ],
    // PROD: keep your already filled-up client email(s) exactly as in your original code
    'prod' => [
        'to' => [
            'nidhishah0002@gmail.com',              // (Kept same as your original "to")
        ],
        'cc' => [
            'info@hbsoftweb.com',                   // (Kept same as your original CC)
        ],
    ],
];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit('Method not allowed');
}

// ----------- Collect & sanitise inputs -----------
$name = isset($_POST['cli_name']) ? trim($_POST['cli_name']) : '';
$email = isset($_POST['cli_email']) ? trim($_POST['cli_email']) : '';
$mobile = isset($_POST['cli_mobile']) ? trim($_POST['cli_mobile']) : '';
$service = isset($_POST['services']) ? trim($_POST['services']) : '';
$date = isset($_POST['date']) ? trim($_POST['date']) : '';

// Basic sanitation
$name = substr(preg_replace("/[\r\n]+/", ' ', strip_tags($name)), 0, 120);
$email = filter_var($email, FILTER_SANITIZE_EMAIL);
$mobile = substr(preg_replace('/\D+/', '', $mobile), 0, 20);
$service = substr(preg_replace("/[\r\n]+/", ' ', strip_tags($service)), 0, 120);
$date = substr(preg_replace("/[\r\n]+/", ' ', strip_tags($date)), 0, 60);

// Validate minimums
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit("Invalid email address.");
}

// ----------- Subject -----------
$subject = "New Appointment Request from {$name}";

// ----------- Bodies -----------
$e = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');

// Plain text
$text = "New Appointment Request\n\n";
$text .= "Name:   {$name}\n";
$text .= "Email:  {$email}\n";
$text .= "Mobile: {$mobile}\n";
$text .= "Service Requested: {$service}\n";
$text .= "Preferred Date:    {$date}\n";

// HTML
$html = '<!doctype html>
<html>
<head><meta charset="UTF-8"><title>New Appointment Request</title></head>
<body style="margin:0;padding:0;background:#ffffff;font-family:Arial,Helvetica,sans-serif;">
  <div style="width:100%;max-width:500px;margin:0 auto;border-top:5px solid #9D7458;padding:20px;">
    <h2 style="margin:0 0 16px 0;font-size:22px;line-height:1.3;text-align:left;">New Appointment Request</h2>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Name:</strong> ' . $e($name) . '</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Email:</strong> ' . $e($email) . '</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Mobile:</strong> ' . $e($mobile) . '</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Service Requested:</strong> ' . $e($service) . '</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Preferred Date:</strong> ' . $e($date) . '</p>
    <p style="margin:16px 0 0 0;font-size:12px;color:#777;">Mode: <strong>' . strtoupper(MODE) . '</strong></p>
  </div>
</body>
</html>';

try {
    $mail = new PHPMailer(true);
    // $mail->SMTPDebug = 2; // uncomment to debug

    // ---- SMTP (Gmail) ----
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    // === Use your Gmail + App Password (keep secret; do not commit) ===
    $mail->Username = 'digital@hbsoftweb.com';  // Gmail address
    $mail->Password = 'kunrcsphngzituka';            // Gmail App Password
    // ================================================================
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // ---- Headers / recipients ----
    // In Gmail SMTP, setFrom should match the authenticated Gmail
    $mail->setFrom('digital@hbsoftweb.com', 'Clinic Appointments');

    // Add TO recipients for current MODE
    if (!empty($RECIPIENTS[MODE]['to'])) {
        foreach ($RECIPIENTS[MODE]['to'] as $to) {
            if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $mail->addAddress($to);
            }
        }
    }

    // Add CC recipients for current MODE
    if (!empty($RECIPIENTS[MODE]['cc'])) {
        foreach ($RECIPIENTS[MODE]['cc'] as $cc) {
            if (filter_var($cc, FILTER_VALIDATE_EMAIL)) {
                $mail->addCC($cc);
            }
        }
    }

    // Let replies go to the submitter
    $mail->addReplyTo($email, ($name !== '' ? $name : 'Website User'));

    // ---- Content ----
    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $html;
    $mail->AltBody = $text;

    // Send
    $mail->send();

    // Redirect on success
    header("Location: thank-you.html");
    exit;

} catch (Exception $ex) {
    http_response_code(500);
    echo "There was an error sending your appointment request. Please try again.";
    error_log('PHPMailer error (appointment): ' . $ex->getMessage());
    exit;
}
