<?php
// subscribe-mail.php — sends footer subscription form via Gmail SMTP (PHPMailer)
// DEV/PROD ready: change MODE to 'prod' later and update recipients below.

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
// Set MODE to 'dev' for now. Change to 'prod' when going live.
const MODE = 'prod';

// Define recipients for each mode (edit prod values when going live)
$RECIPIENTS = [
  'dev' => [
    'to' => [
      'jaymodihbsoftweb@gmail.com',           // YOU (current testing)
    ],
    'cc' => [
      'info@hbsoftweb.com' // New CC (change/remove anytime)
    ],
  ],
  'prod' => [
    'to' => [
      'nidhishah0002@gmail.com',                // <-- Replace with client email(s)
      // 'another@client.com',
    ],
    'cc' => [
      'info@hbsoftweb.com',               // <-- Replace or leave empty
    ],
  ],
];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit('Method not allowed');
}

// ----- Collect & sanitise -----
$email  = isset($_POST['wdt_mc_emailid']) ? filter_var($_POST['wdt_mc_emailid'], FILTER_SANITIZE_EMAIL) : '';
$name   = isset($_POST['wdt_mc_name'])    ? substr(preg_replace("/[\r\n]+/", ' ', strip_tags($_POST['wdt_mc_name'])), 0, 120) : '';
$mobile = isset($_POST['wdt_mc_mobile'])  ? substr(preg_replace('/\D+/', '', $_POST['wdt_mc_mobile']), 0, 20) : '';

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit("Invalid email address.");
}

// ----- Subject & Bodies -----
$subject = "New Subscription Request";

$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

// Plain text
$text  = "New Subscription Request\n\n";
$text .= "Name:   {$name}\n";
$text .= "Mobile: {$mobile}\n";
$text .= "Email:  {$email}\n";

// HTML
$html = '<!doctype html>
<html>
<head><meta charset="UTF-8"><title>New Subscription Request</title></head>
<body style="margin:0;padding:0;background:#ffffff;font-family:Arial,Helvetica,sans-serif;">
  <div style="width:100%;max-width:500px;margin:0 auto;border-top:5px solid #9D7458;padding:20px;">
    <h2 style="margin:0 0 16px 0;font-size:22px;line-height:1.3;text-align:center;">New Subscription Request</h2>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Name:</strong> '.$e($name).'</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Mobile:</strong> '.$e($mobile).'</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Email:</strong> '.$e($email).'</p>
    <p style="margin:16px 0 0 0;font-size:12px;color:#777;">Mode: <strong>'.strtoupper(MODE).'</strong></p>
  </div>
</body>
</html>';

try {
    $mail = new PHPMailer(true);
    // $mail->SMTPDebug = 2; // uncomment to debug

    // ---- SMTP (Gmail) ----
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    // === Replace with your Gmail + App Password (keep secret) ===
    $mail->Username   = 'digital@hbsoftweb.com';   // Gmail address
    $mail->Password   = 'kunrcsphngzituka';             // Gmail App Password
    // ============================================================
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // ---- Headers / recipients ----
    // With Gmail SMTP, setFrom should match the authenticated Gmail
    $mail->setFrom('digital@hbsoftweb.com', 'Website Subscriptions');

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
    $mail->addReplyTo($email, ($name !== '' ? $name : 'Subscriber'));

    // ---- Content ----
    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $html;
    $mail->AltBody = $text;

    // Send
    $mail->send();

    // Redirect on success (no output before this!)
    header("Location: thank-you.html");
    exit;

} catch (Exception $ex) {
    http_response_code(500);
    echo "There was an error sending your subscription request. Please try again.";
    // For debugging on server logs:
    error_log('PHPMailer error: ' . $ex->getMessage());
    exit;
}
