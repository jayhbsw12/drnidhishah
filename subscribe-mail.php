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
      'jaymodihbsoftweb@gmail.com',      // YOU (current testing)
    ],
    'cc' => [
      '' // New CC (change/remove anytime)
    ],
  ],
  'prod' => [
    'to' => [
      'nidhishah0002@gmail.com',         // client email(s)
      // 'another@client.com',
    ],
    'cc' => [
      'info@hbsoftweb.com',              // optional
    ],
  ],
];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  exit('Method not allowed');
}

// ----- Collect & sanitise -----
$email = isset($_POST['wdt_mc_emailid']) ? filter_var($_POST['wdt_mc_emailid'], FILTER_SANITIZE_EMAIL) : '';
$name = isset($_POST['wdt_mc_name']) ? substr(preg_replace("/[\r\n]+/", ' ', strip_tags($_POST['wdt_mc_name'])), 0, 120) : '';
$mobile = isset($_POST['wdt_mc_mobile']) ? substr(preg_replace('/\D+/', '', $_POST['wdt_mc_mobile']), 0, 20) : '';
$location = isset($_POST['location']) ? substr(preg_replace("/[\r\n]+/", ' ', strip_tags($_POST['location'])), 0, 120) : '';

// Identify which form submitted: 'popup' or 'footer' (default to empty)
$form_source = isset($_POST['form_source']) ? trim((string)$_POST['form_source']) : '';

// Validate email only for non-popup forms (footer or others).
if ($form_source !== 'popup') {
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit("Invalid email address.");
  }
} else {
  // For popup submissions we allow empty/invalid email; ensure it's a string
  $email = $email ?: '';
}

// ----- Subject & Bodies -----
$baseSubject = 'Received Inquiry From Website : www.drnidhishah.com';
// Add [DEV] tag to subject in dev mode (remove the ternary if not desired)
$subject = (MODE === 'dev' ? '' : '') . $baseSubject;

$e = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');

// Plain text
$text = $baseSubject . "\n\n";
$text .= "Name:   {$name}\n";
$text .= "Mobile: {$mobile}\n";
$text .= "Email:  {$email}\n";
$text .= "Location: {$location}\n";

// HTML
$html = '<!doctype html>
<html>
<head><meta charset="UTF-8"><title>' . $e($baseSubject) . '</title></head>
<body style="margin:0;padding:0;background:#ffffff;font-family:Arial,Helvetica,sans-serif;">
  <div style="width:100%;max-width:500px;margin:0 auto;border-top:5px solid #9D7458;padding:20px;">
    <h2 style="margin:0 0 16px 0;font-size:22px;line-height:1.3;text-align:center;">' . $e($baseSubject) . '</h2>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Name:</strong> ' . $e($name) . '</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Mobile:</strong> ' . $e($mobile) . '</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Email:</strong> ' . $e($email) . '</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Location:</strong> ' . $e($location) . '</p>
  </div>
</body>
</html>';

try {
  $mail = new PHPMailer(true);
  // $mail->SMTPDebug = 2; // uncomment to debug

  // ---- SMTP (Gmail/Workspace) ----
  $mail->isSMTP();
  $mail->Host = 'smtp.gmail.com';
  $mail->SMTPAuth = true;
  $mail->Username = 'digital@hbsoftweb.com';   // authenticated mailbox
  $mail->Password = 'kunrcsphngzituka';        // app password
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port = 587;

  // From must match the authenticated mailbox
  $mail->setFrom('digital@hbsoftweb.com', 'HB Softweb');

  // Add TO recipients (capture first TO for Reply-To)
  $firstTo = null;
  if (!empty($RECIPIENTS[MODE]['to'])) {
    foreach ($RECIPIENTS[MODE]['to'] as $to) {
      if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
        if ($firstTo === null) {
          $firstTo = $to;
        }
        $mail->addAddress($to);
      }
    }
  }

  // Add CC recipients
  if (!empty($RECIPIENTS[MODE]['cc'])) {
    foreach ($RECIPIENTS[MODE]['cc'] as $cc) {
      if ($cc && filter_var($cc, FILTER_VALIDATE_EMAIL)) {
        $mail->addCC($cc);
      }
    }
  }

  // Reply-To: set to the first TO (client/team). Switch to visitor if you prefer.
  if ($firstTo) {
    $mail->addReplyTo($firstTo, 'Dr. Nidhi Shah');
  }
  // To make replies go to the visitor instead, use:
  // if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
  //   $mail->addReplyTo($email, ($name !== '' ? $name : 'Subscriber'));
  // }

  // ---- Content ----
  $mail->CharSet = 'UTF-8';
  $mail->isHTML(true);
  $mail->Subject = $subject;
  $mail->Body = $html;
  $mail->AltBody = $text;

  // Send
  $mail->send();

  header("Location: thank-you.html");
  exit;

} catch (Exception $ex) {
  http_response_code(500);
  echo "There was an error sending your subscription request. Please try again.";
  error_log('PHPMailer error: ' . $ex->getMessage());
  exit;
}
