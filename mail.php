<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // ----------- Collect & sanitise inputs -----------
    $name    = isset($_POST['cli_name'])   ? trim($_POST['cli_name'])   : '';
    $email   = isset($_POST['cli_email'])  ? trim($_POST['cli_email'])  : '';
    $mobile  = isset($_POST['cli_mobile']) ? trim($_POST['cli_mobile']) : '';
    $service = isset($_POST['services'])   ? trim($_POST['services'])   : '';
    $date    = isset($_POST['date'])       ? trim($_POST['date'])       : '';

    // Basic sanitation (and avoid header injection)
    $name    = substr(preg_replace("/[\r\n]+/", ' ', strip_tags($name)), 0, 120);
    $email   = filter_var($email, FILTER_SANITIZE_EMAIL);
    $mobile  = substr(preg_replace('/\D+/', '', $mobile), 0, 20);
    $service = substr(preg_replace("/[\r\n]+/", ' ', strip_tags($service)), 0, 120);
    $date    = substr(preg_replace("/[\r\n]+/", ' ', strip_tags($date)), 0, 60);

    // ----------- Config -----------
    $to = "nidhishah0002@gmail.com"; // destination
    $from_name  = "Clinic Appointments";        // shown sender name
    $from_email = "no-reply@drnidhishah.com";    // MUST be your domain
    $bounce     = "bounce@drnidhishah.com";      // envelope sender for SPF
    $domain     = "https://drnidhishah.com/";             // used for Message-ID

    // Validate minimums
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        exit("Invalid email address.");
    }

    // ----------- Subject -----------
    $subject = "New Appointment Request from {$name}";
    $cc_recipients  = ["info@hbsoftweb.com"];  
    
    

    // ----------- Build multipart message -----------
    $boundary = "=_".md5(uniqid((string)mt_rand(), true));
    $date_hdr = date(DATE_RFC2822);
    $message_id = sprintf("<%s.%s@%s>", bin2hex(random_bytes(8)), time(), $domain);

    // Plain text (fallback)
    $text  = "New Appointment Request\n\n";
    $text .= "Name:   {$name}\n";
    $text .= "Email:  {$email}\n";
    $text .= "Mobile: {$mobile}\n";
    $text .= "Service Requested: {$service}\n";
    $text .= "Preferred Date:    {$date}\n";

    // HTML version (simple, inline styles; no webfonts)
    $html = '<!doctype html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>New Appointment Request</title>
</head>
<body style="margin:0;padding:0;background:#ffffff;font-family:Arial,Helvetica,sans-serif;">
  <div style="width:100%;max-width:500px;margin:0 auto;border-top:5px solid #9D7458;padding:20px;">
    <h2 style="margin:0 0 16px 0;font-size:22px;line-height:1.3;text-align:left;">New Appointment Request</h2>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Name:</strong> '.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Email:</strong> '.htmlspecialchars($email, ENT_QUOTES, 'UTF-8').'</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Mobile:</strong> '.htmlspecialchars($mobile, ENT_QUOTES, 'UTF-8').'</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Service Requested:</strong> '.htmlspecialchars($service, ENT_QUOTES, 'UTF-8').'</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Preferred Date:</strong> '.htmlspecialchars($date, ENT_QUOTES, 'UTF-8').'</p>
  </div>
</body>
</html>';

    // Headers (use CRLF)
    $headers  = "From: {$from_name} <{$from_email}>\r\n";           // your domain sender
    $headers .= "Reply-To: ".sprintf('"%s" <%s>', $name, $email)."\r\n"; // user stays in Reply-To
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Date: {$date_hdr}\r\n";
    $headers .= "Message-ID: {$message_id}\r\n";
    $headers .= "X-Mailer: PHP/".phpversion()."\r\n";
    // Optional but nice to have:
    // $headers .= "List-Unsubscribe: <mailto:unsubscribe@{$domain}>, <https://{$domain}/unsubscribe>\r\n";

    // Multipart/alternative body
    $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";

    $body  = "--{$boundary}\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $text."\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $html."\r\n";
    $body .= "--{$boundary}--\r\n";

    // Use envelope sender for SPF alignment (fifth param)
    $params = "-f {$bounce}";

    // Send
    $sent = mail($to, $subject, $body, $headers, $params);

    if ($sent) {
        header("Location: thank-you.html");
        exit;
    } else {
        http_response_code(500);
        echo "There was an error sending your appointment request. Please try again.";
    }
}
?>
