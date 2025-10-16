<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/phpmailer/src/Exception.php';
require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';

$TO              = "h.kaushi49@gmail.com";
$FROM_EMAIL      = "MS_U4GLRy@test-3m5jgro19vmgdpyo.mlsender.net"; 
$FROM_NAME       = "Makgrow Impex";
$SMTP_HOST       = "smtp.mailersend.net";
$SMTP_PORT       = 587;
$SMTP_USERNAME   = "MS_U4GLRy@test-3m5jgro19vmgdpyo.mlsender.net"; 
$SMTP_PASSWORD   = "mssp.ytgv4dY.jy7zpl9z6w345vx6.EkeuU6U";   

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || !isset($data['deliveryStatus'])) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid payload']);
  exit;
}

if (strtolower($data['deliveryStatus']) !== 'delivered') {
  echo json_encode(['ok' => true, 'skipped' => true]);
  exit;
}

$ref           = $data['reference']     ?? '(no-ref)';
$buyerName     = $data['buyerName']     ?? '';
$productName   = $data['productName']   ?? '';
$qty           = $data['quantity']      ?? '';
$driver        = $data['driver']        ?? '';
$address       = $data['address']       ?? '';
$city          = $data['city']          ?? '';
$scheduledDate = $data['scheduledDate'] ?? '';
$actualDate    = $data['actualDate']    ?? '';

$subject = "Delivery Completed: {$ref}";
$body = "
<html>
  <body style='font-family:Arial,Helvetica,sans-serif;'>
    <h2>Delivery Completed</h2>
    <table cellpadding='6' cellspacing='0' border='0'>
      <tr><td><strong>Reference</strong></td><td>{$ref}</td></tr>
      <tr><td><strong>Buyer</strong></td><td>{$buyerName}</td></tr>
      <tr><td><strong>Product</strong></td><td>{$productName}</td></tr>
      <tr><td><strong>Quantity</strong></td><td>{$qty}</td></tr>
      <tr><td><strong>Driver</strong></td><td>{$driver}</td></tr>
      <tr><td><strong>Address</strong></td><td>{$address}, {$city}</td></tr>
      <tr><td><strong>Scheduled Date</strong></td><td>{$scheduledDate}</td></tr>
      <tr><td><strong>Actual Date</strong></td><td>{$actualDate}</td></tr>
      <tr><td><strong>Status</strong></td><td>Delivered</td></tr>
    </table>
  </body>
</html>";

try {
  $mail = new PHPMailer(true);
  // $mail->SMTPDebug = 2; 
  $mail->isSMTP();
  $mail->Host       = $SMTP_HOST;
  $mail->SMTPAuth   = true;
  $mail->Username   = $SMTP_USERNAME;
  $mail->Password   = $SMTP_PASSWORD;
  $mail->Port       = $SMTP_PORT;
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
  
  $mail->Timeout    = 15;
  $mail->SMTPKeepAlive = false;
  
  $mail->SMTPOptions = [
    'ssl' => [
      'verify_peer'       => true,
      'verify_peer_name'  => true,
      'allow_self_signed' => false,
    ]
  ];

  $mail->setFrom($FROM_EMAIL, $FROM_NAME);
  $mail->addAddress($TO);

  $mail->isHTML(true);
  $mail->Subject = $subject;
  $mail->Body    = $body;
  $mail->AltBody = strip_tags(
    "Delivery Completed\n".
    "Reference: {$ref}\nBuyer: {$buyerName}\nProduct: {$productName}\nQuantity: {$qty}\n".
    "Driver: {$driver}\nAddress: {$address}, {$city}\nScheduled: {$scheduledDate}\nActual: {$actualDate}\nStatus: Delivered"
  );

  $mail->send();
  echo json_encode(['ok' => true]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Mailer Error: ' . $mail->ErrorInfo]);
}
