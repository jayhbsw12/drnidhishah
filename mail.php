<?php
// mail.php — Appointment form via PHPMailer (Gmail/Workspace SMTP) with DEV/PROD routing

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
    'dev' => [
        'to' => [
            'jaymodihbsoftweb@gmail.com',   // You (testing)
        ],
        'cc' => [
            '',           // Extra CC for dev (optional)
        ],
    ],
    'prod' => [
        'to' => [
            'nidhishah0002@gmail.com',      // Client
            // 'jaymodihbsoftweb@gmail.com',
        ],
        'cc' => [
            // 'digital@hbsoftweb.com',           // Optional CC
            'info@hbsoftweb.com',     
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
$location = isset($_POST['location']) ? trim($_POST['location']) : '';

$name = substr(preg_replace("/[\r\n]+/", ' ', strip_tags($name)), 0, 120);
$email = filter_var($email, FILTER_SANITIZE_EMAIL);
$mobile = substr(preg_replace('/\D+/', '', $mobile), 0, 20);
$service = substr(preg_replace("/[\r\n]+/", ' ', strip_tags($service)), 0, 120);
$date = substr(preg_replace("/[\r\n]+/", ' ', strip_tags($date)), 0, 60);
$location = substr(preg_replace("/[\r\n]+/", ' ', strip_tags($location)), 0, 120);

// Validate minimums
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit("Invalid email address.");
}

// ----------- Subject -----------
$baseSubject = 'Received Appointment Request From Website : www.drnidhishah.com';
$subject = (MODE === 'dev' ? '' : '') . $baseSubject; 

// ----------- Bodies -----------
$e = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');

// Plain text
$text = $baseSubject . "\n\n";
$text .= "Name:   {$name}\n";
$text .= "Email:  {$email}\n";
$text .= "Mobile: {$mobile}\n";
$text .= "Service Requested: {$service}\n";
$text .= "Preferred Date:    {$date}\n";
$text .= "Location: {$location}\n";

// HTML
$html = '<!doctype html>
<html>
<head><meta charset="UTF-8"><title>' . $e($baseSubject) . '</title></head>
<body style="margin:0;padding:0;background:#ffffff;font-family:Arial,Helvetica,sans-serif;">
  <div style="width:100%;max-width:500px;margin:0 auto;border-top:5px solid #9D7458;padding:20px;">
    <h2 style="margin:0 0 16px 0;font-size:22px;line-height:1.3;text-align:left;">' . $e($baseSubject) . '</h2>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Name:</strong> ' . $e($name) . '</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Email:</strong> ' . $e($email) . '</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Mobile:</strong> ' . $e($mobile) . '</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Service Requested:</strong> ' . $e($service) . '</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Preferred Date:</strong> ' . $e($date) . '</p>
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

    // Reply-To: same pattern as subscribe script (reply to client/team)
    if ($firstTo) {
        $mail->addReplyTo($firstTo, 'Dr. Nidhi Shah');
    }
    // (Optional) To reply to the visitor instead, swap to:
    // if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    //   $mail->addReplyTo($email, ($name !== '' ? $name : 'Website User'));
    // }

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
