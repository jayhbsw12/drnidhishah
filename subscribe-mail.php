<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // ----- Collect & sanitise -----
    $email  = isset($_POST['wdt_mc_emailid']) ? filter_var($_POST['wdt_mc_emailid'], FILTER_SANITIZE_EMAIL) : '';
    $name   = isset($_POST['wdt_mc_name'])    ? substr(preg_replace("/[\r\n]+/", ' ', strip_tags($_POST['wdt_mc_name'])), 0, 120) : '';
    $mobile = isset($_POST['wdt_mc_mobile'])  ? substr(preg_replace('/\D+/', '', $_POST['wdt_mc_mobile']), 0, 20) : '';

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        exit("Invalid email address.");
    }

    // ----- Config -----
    $to         = "nidhishah0002@gmail.com";  // destination
    $from_name  = "Website Subscriptions";
    $from_email = "no-reply@drnidhishah.com";             // MUST be your domain
    $bounce     = "bounce@drnidhishah.com";               // envelope sender for SPF
    $domain     = "drnidhishah.com";                       // for Message-ID/ident

    $subject = "New Subscription Request";

    // Add CC recipients here
    $cc_recipients = ["priyankatiwari1420@gmail.com"];  // CC email addresses

    // ----- Build multipart message -----
    $boundary   = "=_".md5(uniqid((string)mt_rand(), true));
    $date_hdr   = date(DATE_RFC2822);
    $message_id = sprintf("<%s.%s@%s>", bin2hex(random_bytes(8)), time(), $domain);

    // Plain-text fallback
    $text  = "New Subscription Request\n\n";
    $text .= "Name:   {$name}\n";
    $text .= "Mobile: {$mobile}\n";
    $text .= "Email:  {$email}\n";

    // Simple HTML version
    $html = '<!doctype html>
<html>
<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><title>New Subscription Request</title></head>
<body style="margin:0;padding:0;background:#ffffff;font-family:Arial,Helvetica,sans-serif;">
  <div style="width:100%;max-width:500px;margin:0 auto;border-top:5px solid #9D7458;padding:20px;">
    <h2 style="margin:0 0 16px 0;font-size:22px;line-height:1.3;text-align:center;">New Subscription Request</h2>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Name:</strong> '.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Mobile:</strong> '.htmlspecialchars($mobile, ENT_QUOTES, 'UTF-8').'</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Email:</strong> '.htmlspecialchars($email, ENT_QUOTES, 'UTF-8').'</p>
  </div>
</body>
</html>';

    // Headers
    $headers  = "From: {$from_name} <{$from_email}>\r\n";                 // your domain sender
    $headers .= "Reply-To: ".sprintf('"%s" <%s>', $name ?: 'Subscriber', $email)."\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Date: {$date_hdr}\r\n";
    $headers .= "Message-ID: {$message_id}\r\n";
    $headers .= "X-Mailer: PHP/".phpversion()."\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";

    // Add CC recipients
    $headers .= "Cc: " . implode(", ", $cc_recipients) . "\r\n";  // Add CC to headers

    // Body
    $body  = "--{$boundary}\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $text."\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $html."\r\n";
    $body .= "--{$boundary}--\r\n";

    // Envelope sender for SPF alignment
    $params = "-f {$bounce}";

    // Send email
    if (mail($to, $subject, $body, $headers, $params)) {
        header("Location: thank-you.html");
        exit;
    } else {
        http_response_code(500);
        echo "There was an error sending your subscription request. Please try again.";
    }
}
?>
